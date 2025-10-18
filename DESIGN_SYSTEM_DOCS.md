# GEMS2 Design System – Module Page Template

This guide codifies the modern module page concept used in License and other admin/operations pages: a gradient header, a metrics row, a filter card, and a list (DataTable) that stacks into cards on mobile.

## Visual tokens

- Colors
	- Primary: #4338CA
	- Background: #F1F5F9
	- Text: #0F172A
	- Muted: #64748B
- Corners: large border-radius 24–28px for cards and header blocks
- Shadows: soft shadow (e.g., 0 6px 16px rgba(2, 6, 23, 0.08)) on major cards
- Spacing: generous padding (24–28px) inside cards; gap 12–16px between elements

## Page structure

Use this top-to-bottom layout on all new module pages.

1) Header (gradient) with title, short description, and primary actions
2) Metrics row (3–4 stat cards)
3) Filter card with search and simple dropdowns; hide DataTables’ built-in filter
4) Main list in a styled card (DataTable); stacks on mobile

### HTML skeleton

Wrap the page in a distinct container with a module prefix (replace Module with your page name):

<div class="module-page module-Module">
	<section class="module-page-header">
		<div class="header-text">
			<h2>Module Title</h2>
			<p>Short one-line description.</p>
		</div>
		<div class="header-actions">
			<button id="btnModuleRefresh" class="btn btn-light">Refresh</button>
			<button id="btnModuleAdd" class="btn btn-primary">Add</button>
		</div>
	</section>

	<section class="metrics-row">
		<div class="metric-card">
			<div class="metric-label">Total</div>
			<div class="metric-value" id="metricTotal">0</div>
			<div class="metric-subtext">All records</div>
		</div>
		<div class="metric-card">
			<div class="metric-label">Expiring</div>
			<div class="metric-value" id="metricExpiring">0</div>
			<div class="metric-subtext">Within threshold</div>
		</div>
		<div class="metric-card">
			<div class="metric-label">Expired</div>
			<div class="metric-value" id="metricExpired">0</div>
			<div class="metric-subtext">Past due</div>
		</div>
		<div class="metric-card">
			<div class="metric-label">No expiry</div>
			<div class="metric-value" id="metricNoExpiry">0</div>
			<div class="metric-subtext">N/A</div>
		</div>
	</section>

	<section class="filter-card">
		<div class="filter-grid">
			<div>
				<label class="filter-label" for="txtModuleSearch">Search</label>
				<input type="text" id="txtModuleSearch" class="form-control" placeholder="Search..." />
			</div>
			<div>
				<label class="filter-label" for="optModuleStatus">Status</label>
				<select id="optModuleStatus" class="form-control">
					<option value="">All</option>
					<option value="active">Active</option>
					<option value="expired">Expired</option>
					<option value="expiring">Expiring</option>
					<option value="no_expiry">No expiry</option>
				</select>
			</div>
			<div class="summary-pill">
				<span id="lblModuleCount">Showing 0 of 0</span>
				<span id="lblModuleUpdated">—</span>
			</div>
		</div>
	</section>

	<section class="module-data-card">
		<div class="module-table-wrapper">
			<table id="dtModule" class="table table-striped table-hover" style="width:100%">
				<thead>
					<tr>
						<th>#</th>
						<th>Name</th>
						<th>Status</th>
						<th>Valid To</th>
						<th>Days</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</section>
</div>

### CSS blueprint

Scope styles by the page container (e.g., .module-Module) and apply these patterns:

/* Header */
.module-page-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	background: linear-gradient(135deg, #4338CA, #6366F1);
	color: #fff;
	padding: 24px 28px;
	border-radius: 24px;
	box-shadow: 0 6px 16px rgba(2, 6, 23, 0.08);
}
.module-page-header .header-text h2 { margin: 0 0 4px; font-weight: 700; }
.module-page-header .header-text p { margin: 0; opacity: 0.9; }
.module-page-header .header-actions .btn { margin-left: 8px; }

/* Metrics */
.metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 16px; }
.metric-card { background: #fff; border-radius: 20px; padding: 20px; box-shadow: 0 6px 16px rgba(2, 6, 23, 0.06); }
.metric-label { color: #64748B; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
.metric-value { color: #0F172A; font-size: 28px; font-weight: 700; margin-top: 4px; }
.metric-subtext { color: #64748B; font-size: 12px; margin-top: 2px; }

/* Filters */
.filter-card { background: #fff; border-radius: 20px; padding: 18px; margin: 16px 0; box-shadow: 0 6px 16px rgba(2, 6, 23, 0.06); }
.filter-grid { display: grid; grid-template-columns: 1fr 220px 1fr; align-items: end; gap: 16px; }
.filter-label { display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; }
.summary-pill { justify-self: end; align-self: center; color: #0F172A; }

/* List card */
.module-data-card { background: #fff; border-radius: 20px; padding: 0; box-shadow: 0 6px 16px rgba(2, 6, 23, 0.06); }
.module-table-wrapper { padding: 12px; }

/* Mobile stack for table at ≤768px */
@media (max-width: 768px) {
	.metrics-row { grid-template-columns: 1fr 1fr; }
	.filter-grid { grid-template-columns: 1fr; }
	.module-table-wrapper thead { display: none; }
	.module-table-wrapper table, .module-table-wrapper tbody, .module-table-wrapper tr, .module-table-wrapper td { display: block; width: 100%; }
	.module-table-wrapper tr { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 12px; padding: 8px 12px; box-shadow: 0 4px 10px rgba(2, 6, 23, 0.04); }
	.module-table-wrapper td { border: 0; border-bottom: 1px dashed #e5e7eb; padding: 8px 0; }
	.module-table-wrapper td:last-child { border-bottom: 0; }
	.module-table-wrapper td::before { content: attr(data-label); display: block; color: #64748B; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
}

### JavaScript blueprint

- Use a dedicated JS file at js/pages/main_module.js (replace module name) and wire it from the HTML.
- Hide DataTables’ built-in filter (dom: 'lrtip') and drive search from txtModuleSearch.
- Add a custom status filter with $.fn.dataTable.ext.search.
- Compute metrics from your fetched array and update metric* elements.
- After each draw, update lblModuleCount (“Showing X of Y”) and lblModuleUpdated with a timestamp.
- For mobile stacking, set data-label for each TD in rowCallback.

Example initialization outline:

$(function () {
	var data = []; // fetch via mzAjaxRequest
	var table = $('#dtModule').DataTable({
		data: data,
		dom: 'lrtip',
		columns: [
			{ data: null }, // index
			{ data: 'name' },
			{ data: 'status' },
			{ data: 'validTo' },
			{ data: 'days' },
			{ data: null } // actions
		],
		createdRow: function (row, rowData) {
			// mark expiring/expired rows if needed
			if (rowData.days < 0) $(row).addClass('row-expired');
			else if (rowData.days >= 0 && rowData.days <= 30) $(row).addClass('row-expiring');
		},
		rowCallback: function (row, rowData) {
			// set data-labels for mobile
			$('td', row).eq(0).attr('data-label', '#');
			$('td', row).eq(1).attr('data-label', 'Name');
			$('td', row).eq(2).attr('data-label', 'Status');
			$('td', row).eq(3).attr('data-label', 'Valid To');
			$('td', row).eq(4).attr('data-label', 'Days');
			$('td', row).eq(5).attr('data-label', 'Actions');
		}
	});

	// row numbering
	table.on('order.dt search.dt', function () {
		let i = 1; table.cells(null, 0, { search: 'applied', order: 'applied' }).every(function () { this.data(i++); });
	}).draw();

	// custom search
	$('#txtModuleSearch').on('keyup change', function () { table.search(this.value).draw(); });

	// custom status filter
	$.fn.dataTable.ext.search.push(function (settings, dataArr, dataIndex) {
		var sel = $('#optModuleStatus').val();
		if (!sel) return true;
		var status = dataArr[2] || '';
		return status.toLowerCase().indexOf(sel.toLowerCase()) !== -1;
	});
	$('#optModuleStatus').on('change', function () { table.draw(); });

	// metrics and summary updates
	function updateMetrics(arr) {
		var total = arr.length;
		var expired = arr.filter(x => x.days < 0).length;
		var expiring = arr.filter(x => x.days >= 0 && x.days <= 30).length;
		var noExpiry = arr.filter(x => x.noExpiry).length;
		$('#metricTotal').text(total);
		$('#metricExpired').text(expired);
		$('#metricExpiring').text(expiring);
		$('#metricNoExpiry').text(noExpiry);
	}
	function updateSummary() {
		var info = table.page.info();
		$('#lblModuleCount').text(`Showing ${info.recordsDisplay} of ${info.recordsTotal}`);
		$('#lblModuleUpdated').text(new Date().toLocaleString());
	}
	updateMetrics(data);
	table.on('draw', updateSummary);
	updateSummary();
});

### Role/site behavior

- Use mzIsRoleExist('1,10') to check admin privileges; hide or disable site dropdowns for non-admins.
- Use mzGetUserInfoByParam('siteId') to pin the site for non-admins. Prefer calling backend APIs with /site/{siteId} variants when available.

### Accessibility and UX notes

- Ensure color contrast for text on gradient header remains AA compliant.
- All interactive elements must have visible focus and meaningful labels.
- Keep mobile tap targets at least 40px in height.

### Reference implementation

- License page: license.html + js/pages/main_license.js
- Zone page (template download pattern): zone.html + js/pages/main_zone.js

