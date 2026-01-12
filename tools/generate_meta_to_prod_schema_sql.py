import json
import pathlib
import re
from collections import defaultdict

ROOT = pathlib.Path(__file__).resolve().parents[1]
DOCS = ROOT / "docs"
OUT_DIR = ROOT / "generated_sql" / "meta_to_prod_gfm"

META_PATH = DOCS / "meta-gems.coffee"
PROD_PATH = DOCS / "prod_gfm.coffee"


def qident(name: str) -> str:
    return f"`{name.replace('`', '``')}`"


def infer_charset_from_collation(collation: str | None) -> str | None:
    if not collation or "_" not in collation:
        return None
    return collation.split("_", 1)[0]


def is_null_default(v) -> bool:
    return v is None or v == "NULL"


def sql_literal(value, data_type: str | None = None) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, (int, float)):
        return str(value)

    s = str(value)
    up = s.upper().strip()

    if up in {"CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP()", "NOW()", "CURDATE()", "UUID()"}:
        return s

    # Some defaults in information_schema (notably enums) are already returned
    # as quoted SQL literals, e.g. "'pending'".
    if s.startswith("'") and s.endswith("'") and len(s) >= 2:
        return s

    if data_type and data_type.lower() in {
        "tinyint",
        "smallint",
        "mediumint",
        "int",
        "integer",
        "bigint",
        "decimal",
        "double",
        "float",
        "real",
        "bit",
        "year",
    }:
        if re.fullmatch(r"-?\d+(?:\.\d+)?", s.strip()):
            return s.strip()

    s = s.replace("\\", "\\\\").replace("'", "''")
    return f"'{s}'"


def column_def(col: dict) -> str:
    name = qident(col["COLUMN_NAME"])
    col_type = col["COLUMN_TYPE"]
    nullable = col["IS_NULLABLE"] == "YES"
    default = col.get("COLUMN_DEFAULT", None)
    extra = (col.get("EXTRA") or "").strip()
    comment = col.get("COLUMN_COMMENT") or ""
    charset = col.get("CHARACTER_SET_NAME")
    collation = col.get("COLLATION_NAME")

    parts: list[str] = [name, col_type]

    if charset:
        parts.append(f"CHARACTER SET {charset}")
    if collation:
        parts.append(f"COLLATE {collation}")

    parts.append("NULL" if nullable else "NOT NULL")

    if not is_null_default(default):
        parts.append(f"DEFAULT {sql_literal(default, col.get('DATA_TYPE'))}")
    else:
        if nullable and default == "NULL":
            parts.append("DEFAULT NULL")

    if extra:
        parts.append(extra)

    if comment:
        parts.append(f"COMMENT {sql_literal(comment)}")

    return " ".join(parts)


def indexes_by_name(tbl: dict) -> dict:
    out = {}
    for idx in tbl.get("indexes", []) or []:
        cols = sorted(idx.get("columns", []) or [], key=lambda c: c.get("seq_in_index", 0))
        col_expr: list[str] = []
        for c in cols:
            expr = qident(c["column_name"])
            if c.get("sub_part") is not None:
                expr += f"({int(c['sub_part'])})"
            col_expr.append(expr)
        out[idx["index_name"]] = {
            "non_unique": int(idx.get("non_unique", 1)),
            "index_type": idx.get("index_type", "BTREE"),
            "columns": col_expr,
        }
    return out


def fk_by_constraint(tbl: dict) -> dict:
    grouped: dict[str, list[dict]] = defaultdict(list)
    for fk in tbl.get("foreign_keys", []) or []:
        grouped[fk["CONSTRAINT_NAME"]].append(fk)

    out = {}
    for name, rows in grouped.items():
        cols = [qident(r["COLUMN_NAME"]) for r in rows]
        ref_table = qident(rows[0]["REFERENCED_TABLE_NAME"])
        ref_cols = [qident(r["REFERENCED_COLUMN_NAME"]) for r in rows]
        out[name] = {
            "columns": cols,
            "ref_table": ref_table,
            "ref_columns": ref_cols,
            "on_update": rows[0].get("UPDATE_RULE", "RESTRICT"),
            "on_delete": rows[0].get("DELETE_RULE", "RESTRICT"),
        }
    return out


def table_options(tbl: dict) -> str:
    info = tbl.get("table_info", {})
    engine = info.get("ENGINE")
    coll = info.get("TABLE_COLLATION")
    charset = infer_charset_from_collation(coll)

    # For views, information_schema.TABLES.ENGINE is typically NULL. We should not
    # emit table options that would accidentally turn a view into a physical table.
    options = []
    if engine:
        options.append(f"ENGINE={engine}")
    if charset:
        options.append(f"DEFAULT CHARSET={charset}")
    if coll:
        options.append(f"COLLATE={coll}")

    comment = info.get("TABLE_COMMENT")
    if comment:
        options.append(f"COMMENT={sql_literal(comment)}")

    return " ".join(options)


def create_table_sql(name: str, tbl: dict) -> str:
    cols = tbl.get("columns", []) or []
    lines = [column_def(c) for c in cols]

    idxs = indexes_by_name(tbl)
    if "PRIMARY" in idxs:
        pk_cols = ", ".join(idxs["PRIMARY"]["columns"])
        lines.append(f"PRIMARY KEY ({pk_cols})")

    opts = table_options(tbl)
    suffix = f" {opts}" if opts else ""
    return f"CREATE TABLE IF NOT EXISTS {qident(name)} (\n  " + ",\n  ".join(lines) + f"\n){suffix};"


def is_view_table(tbl: dict) -> bool:
    info = tbl.get("table_info", {})
    # meta-gems schema extractor marks views with ENGINE=null and TABLE_COMMENT='VIEW'
    return info.get("ENGINE") is None and (info.get("TABLE_COMMENT") == "VIEW")


def auto_increment_column_name(tbl: dict) -> str | None:
    for c in (tbl.get("columns", []) or []):
        extra = (c.get("EXTRA") or "").lower()
        if "auto_increment" in extra:
            return c.get("COLUMN_NAME")
    return None


def has_primary_key(tbl: dict) -> bool:
    idxs = indexes_by_name(tbl)
    return "PRIMARY" in idxs and bool(idxs["PRIMARY"].get("columns"))


def has_index_on_column(tbl: dict, column_name: str) -> bool:
    idxs = indexes_by_name(tbl)
    needle = qident(column_name)
    for idx in idxs.values():
        if needle in (idx.get("columns") or []):
            return True
    return False


def column_def_with_overrides(col: dict, *, force_nullable: bool | None = None, force_extra: str | None = None) -> str:
    c = dict(col)
    if force_nullable is not None:
        c["IS_NULLABLE"] = "YES" if force_nullable else "NO"
    if force_extra is not None:
        c["EXTRA"] = force_extra
    return column_def(c)


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    meta = json.loads(META_PATH.read_text(encoding="utf-8"))
    prod = json.loads(PROD_PATH.read_text(encoding="utf-8"))

    meta_tables = meta["tables"]
    prod_tables = prod["tables"]

    meta_names = set(meta_tables)
    prod_names = set(prod_tables)

    # Split missing into base tables vs views (we cannot recreate views without definition)
    raw_missing = sorted(meta_names - prod_names)
    missing_views = [t for t in raw_missing if is_view_table(meta_tables[t])]
    missing_tables = [t for t in raw_missing if t not in set(missing_views)]
    extra_tables = sorted(prod_names - meta_names)
    common_tables = sorted(meta_names & prod_names)

    missing_cols: dict[str, list[dict]] = defaultdict(list)
    changed_cols: dict[str, list[tuple[dict, dict, list[str]]]] = defaultdict(list)
    missing_indexes: dict[str, list[tuple[str, dict]]] = defaultdict(list)
    missing_fks: dict[str, list[tuple[str, dict]]] = defaultdict(list)

    for t in common_tables:
        mt = meta_tables[t]
        pt = prod_tables[t]

        mcols = {c["COLUMN_NAME"]: c for c in (mt.get("columns", []) or [])}
        pcols = {c["COLUMN_NAME"]: c for c in (pt.get("columns", []) or [])}

        for colname, mcol in mcols.items():
            if colname not in pcols:
                missing_cols[t].append(mcol)
            else:
                pcol = pcols[colname]
                keys = [
                    "COLUMN_TYPE",
                    "IS_NULLABLE",
                    "COLUMN_DEFAULT",
                    "EXTRA",
                    "CHARACTER_SET_NAME",
                    "COLLATION_NAME",
                    "COLUMN_COMMENT",
                ]
                diffs = [k for k in keys if (mcol.get(k) or None) != (pcol.get(k) or None)]
                if diffs:
                    changed_cols[t].append((mcol, pcol, diffs))

        midx = indexes_by_name(mt)
        pidx = indexes_by_name(pt)
        for idx_name, idx in midx.items():
            if idx_name == "PRIMARY":
                continue
            if idx_name not in pidx or idx != pidx[idx_name]:
                missing_indexes[t].append((idx_name, idx))

        mfk = fk_by_constraint(mt)
        pfk = fk_by_constraint(pt)
        for cname, fk in mfk.items():
            if cname not in pfk or fk != pfk[cname]:
                missing_fks[t].append((cname, fk))

    report_lines: list[str] = []
    report_lines.append(f"Meta tables: {len(meta_names)}")
    report_lines.append(f"Prod tables: {len(prod_names)}")
    report_lines.append(f"Missing tables in prod: {len(missing_tables)}")
    report_lines.append(f"Extra tables in prod (not in meta): {len(extra_tables)}")
    report_lines.append("")
    report_lines.append("Missing tables:")
    for n in missing_tables:
        report_lines.append(f"- {n}")
    report_lines.append("")
    report_lines.append("Missing views (definition not included in meta schema dump):")
    for n in missing_views:
        report_lines.append(f"- {n}")
    report_lines.append("")
    report_lines.append("Extra tables:")
    for n in extra_tables:
        report_lines.append(f"- {n}")
    report_lines.append("")

    report_lines.append("Per-table diffs (counts):")
    for t in common_tables:
        mc = len(missing_cols.get(t, []))
        cc = len(changed_cols.get(t, []))
        mi = len(missing_indexes.get(t, []))
        mf = len(missing_fks.get(t, []))
        if mc or cc or mi or mf:
            report_lines.append(
                f"- {t}: add_cols={mc}, change_cols={cc}, add_or_fix_indexes={mi}, add_or_fix_fks={mf}"
            )

    (OUT_DIR / "schema_diff_report.txt").write_text("\n".join(report_lines) + "\n", encoding="utf-8")

    preflight = (
        "-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee\n"
        "-- Target: MariaDB 10.4 (XAMPP). Review before running in production.\n"
        "-- Forward-only and avoids DROPs.\n\n"
        "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\n"
        "SET FOREIGN_KEY_CHECKS=0;\n\n"
    )
    postflight = "\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n"

    # 01 create missing tables
    sql1: list[str] = [preflight]
    for name in missing_tables:
        sql1.append(create_table_sql(name, meta_tables[name]))
        sql1.append("")
    sql1.append(postflight)
    (OUT_DIR / "01_create_missing_tables.sql").write_text("\n".join(sql1), encoding="utf-8")

    # 01b placeholder for missing views
    if missing_views:
        sql1b: list[str] = [
            "-- Missing VIEW definitions\n"
            "-- The schema JSON does not include the SELECT body for views.\n"
            "-- Get them from the source DB using: SHOW CREATE VIEW <view_name>;\n\n"
        ]
        for view_name in missing_views:
            sql1b.append(f"-- TODO: Create view {view_name}")
            sql1b.append(f"-- SHOW CREATE VIEW {qident(view_name)};\n")
        (OUT_DIR / "01b_missing_views_TODO.sql").write_text("\n".join(sql1b), encoding="utf-8")

    # 02 add missing columns (with AFTER/FIRST)
    # Notes:
    # - Adding a NOT NULL column with no DEFAULT can fail on populated tables.
    #   We add it as NULL first (for existing tables), and leave strict alignment
    #   to the optional modify script.
    # - Adding AUTO_INCREMENT requires the column to be indexed, and only one
    #   AUTO_INCREMENT column can exist per table.
    sql2: list[str] = [preflight]
    warnings: list[str] = []
    for t, cols in missing_cols.items():
        mt_cols = [c["COLUMN_NAME"] for c in (meta_tables[t].get("columns", []) or [])]
        pt_cols = {c["COLUMN_NAME"] for c in (prod_tables[t].get("columns", []) or [])}

        prod_ai = auto_increment_column_name(prod_tables[t])
        prod_has_pk = has_primary_key(prod_tables[t])

        for mcol in cols:
            cname = mcol["COLUMN_NAME"]
            idx = mt_cols.index(cname)
            after = None
            for j in range(idx - 1, -1, -1):
                prev = mt_cols[j]
                if prev in pt_cols:
                    after = prev
                    break
            position = "FIRST" if after is None else f"AFTER {qident(after)}"

            m_extra = (mcol.get("EXTRA") or "").lower().strip()
            wants_auto_inc = "auto_increment" in m_extra
            wants_not_null = (mcol.get("IS_NULLABLE") == "NO")
            has_default = not is_null_default(mcol.get("COLUMN_DEFAULT", None))

            # For existing tables, avoid failing DDL when adding NOT NULL columns with no default.
            force_nullable = False
            if wants_not_null and not has_default and not wants_auto_inc:
                force_nullable = True
                warnings.append(
                    f"{t}.{cname}: meta is NOT NULL with no DEFAULT; added as NULL to avoid failing on existing rows."
                )
                sql2.append(
                    f"-- NOTE: {t}.{cname} is NOT NULL in meta but has no DEFAULT; added as NULL for safety."
                )

            # AUTO_INCREMENT columns are special:
            # - only one per table
            # - must be indexed (PRIMARY KEY or UNIQUE/KEY)
            if wants_auto_inc:
                if prod_ai and prod_ai != cname:
                    # Table already has AUTO_INCREMENT. We cannot add another.
                    warnings.append(
                        f"{t}.{cname}: meta is AUTO_INCREMENT, but prod already has AUTO_INCREMENT column '{prod_ai}'. Added without AUTO_INCREMENT as NULL."
                    )
                    sql2.append(
                        f"-- WARNING: meta defines {t}.{cname} as AUTO_INCREMENT, but prod already has AUTO_INCREMENT '{prod_ai}'."
                    )
                    sql2.append(
                        f"-- WARNING: added {t}.{cname} without AUTO_INCREMENT and as NULL. If you need to migrate keys, do it manually."
                    )
                    col_sql = column_def_with_overrides(mcol, force_nullable=True, force_extra="")
                    sql2.append(f"ALTER TABLE {qident(t)} ADD COLUMN {col_sql} {position};")
                else:
                    # No conflicting AUTO_INCREMENT, but ensure it is indexed.
                    col_sql = column_def_with_overrides(mcol)
                    if has_index_on_column(prod_tables[t], cname):
                        sql2.append(f"ALTER TABLE {qident(t)} ADD COLUMN {col_sql} {position};")
                    else:
                        # Add a key in the same ALTER so MariaDB accepts AUTO_INCREMENT.
                        if not prod_has_pk and mcol.get("COLUMN_KEY") == "PRI":
                            sql2.append(
                                f"ALTER TABLE {qident(t)} ADD COLUMN {col_sql} {position}, ADD PRIMARY KEY ({qident(cname)});"
                            )
                        else:
                            key_name = f"uk_{t}_{cname}"
                            sql2.append(
                                f"ALTER TABLE {qident(t)} ADD COLUMN {col_sql} {position}, ADD UNIQUE KEY {qident(key_name)} ({qident(cname)});"
                            )
            else:
                col_sql = column_def_with_overrides(mcol, force_nullable=force_nullable)
                sql2.append(f"ALTER TABLE {qident(t)} ADD COLUMN {col_sql} {position};")

            pt_cols.add(cname)

        if cols:
            sql2.append("")

    sql2.append(postflight)
    (OUT_DIR / "02_add_missing_columns.sql").write_text("\n".join(sql2), encoding="utf-8")

    # 03 add indexes
    sql3: list[str] = [preflight]
    for t, idxs in missing_indexes.items():
        for idx_name, idx in idxs:
            cols = ", ".join(idx["columns"])
            if int(idx["non_unique"]) == 0:
                sql3.append(f"ALTER TABLE {qident(t)} ADD UNIQUE KEY {qident(idx_name)} ({cols});")
            else:
                sql3.append(f"CREATE INDEX {qident(idx_name)} ON {qident(t)} ({cols});")
        if idxs:
            sql3.append("")

    sql3.append(postflight)
    (OUT_DIR / "03_add_indexes.sql").write_text("\n".join(sql3), encoding="utf-8")

    # 04 add foreign keys
    sql4: list[str] = [preflight]
    for t, fks in missing_fks.items():
        for cname, fk in fks:
            cols = ", ".join(fk["columns"])
            ref_cols = ", ".join(fk["ref_columns"])
            sql4.append(
                f"ALTER TABLE {qident(t)} ADD CONSTRAINT {qident(cname)} FOREIGN KEY ({cols}) "
                f"REFERENCES {fk['ref_table']} ({ref_cols}) ON DELETE {fk['on_delete']} ON UPDATE {fk['on_update']};"
            )
        if fks:
            sql4.append("")

    sql4.append(postflight)
    (OUT_DIR / "04_add_foreign_keys.sql").write_text("\n".join(sql4), encoding="utf-8")

    # 05 optional strict modify
    sql5: list[str] = [
        "-- OPTIONAL: strict column modifications to match meta exactly.\n"
        "-- Review carefully: some changes can be destructive (NOT NULL, shrinking varchar, charset/collation).\n\n"
        + preflight
    ]

    for t, diffs in changed_cols.items():
        for mcol, _pcol, keys in diffs:
            sql5.append(f"-- {t}.{mcol['COLUMN_NAME']} differs in: {', '.join(keys)}")
            sql5.append(f"ALTER TABLE {qident(t)} MODIFY COLUMN {column_def(mcol)};")
        if diffs:
            sql5.append("")

    sql5.append(postflight)
    (OUT_DIR / "05_optional_strict_modify_columns.sql").write_text("\n".join(sql5), encoding="utf-8")

    if warnings:
        # Append warnings to the report for visibility.
        report_path = OUT_DIR / "schema_diff_report.txt"
        report_path.write_text(
            report_path.read_text(encoding="utf-8")
            + "\nWarnings:\n"
            + "\n".join(f"- {w}" for w in warnings)
            + "\n",
            encoding="utf-8",
        )

    print(f"Wrote SQL to: {OUT_DIR}")
    print(
        f"Missing tables: {len(missing_tables)}; Missing views: {len(missing_views)}; Extra tables: {len(extra_tables)}"
    )
    print(
        f"Tables with missing cols: {sum(1 for _, v in missing_cols.items() if v)}; "
        f"changed cols: {sum(1 for _, v in changed_cols.items() if v)}"
    )


if __name__ == "__main__":
    main()
