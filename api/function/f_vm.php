<?php
class Class_vm {
    private $constant; private $fn_general; private $fn_login;
    public function __set($n,$v){$this->$n=$v;}

    private function sanitize($s){
        if ($s===null) return '';
        $s = trim($s);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',$s);
        return $s;
    }

    public function validateVisitPayload($data){
        $errors=[];
        $get=function($k) use ($data){return array_key_exists($k,$data)?$data[$k]:null;};
        $name=$this->sanitize($get('name')); if($name===''||strlen($name)>100) $errors['name']='Name required (1-100 chars)';
        $contact=$this->sanitize($get('contact_no')); if($contact==='') $errors['contact_no']='Contact required';
        elseif(!preg_match('/^[0-9\-\+\(\)\s]{3,50}$/',$contact)) $errors['contact_no']='Invalid contact format';
        $ic=$this->sanitize($get('ic_no')); if($ic===''||!preg_match('/^[0-9]{6,20}$/',$ic)) $errors['ic_no']='IC/ID must be 6-20 digits';
        $company=$this->sanitize($get('company')); if(strlen($company)>100) $errors['company']='Max 100 chars';
        $email=$this->sanitize($get('email')); if($email!=='' && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i',$email)) $errors['email']='Invalid email';
        $party=$get('party_size'); if(!preg_match('/^[0-9]{1,3}$/',strval($party))||intval($party)<1||intval($party)>999) $errors['party_size']='Party size 1-999';
        // host can now be provided via host_id (preferred) or legacy host_name text during transition
        $hostIdRaw = $get('host_id');
        $hostId = null;
        if($hostIdRaw!==null && $hostIdRaw!=='') {
            if(!preg_match('/^[0-9]+$/', strval($hostIdRaw))) {
                $errors['host_id'] = 'host_id must be numeric';
            } else {
                $hostId = intval($hostIdRaw);
            }
        }
        $host=$this->sanitize($get('host_name'));
        if($hostId===null){ // only enforce text host when host_id not supplied
            if($host===''||strlen($host)>100) $errors['host_name']='Host required (1-100 chars)';
        } else { // if host_id supplied, host_name is optional but if provided must be <=100
            if($host!=='' && strlen($host)>100) $errors['host_name']='Max 100 chars';
        }
        $purpose=$this->sanitize($get('purpose')); if($purpose===''||strlen($purpose)>2000) $errors['purpose']='Purpose 1-2000 chars';
        $siteId=$get('site_id'); if(!preg_match('/^[0-9]+$/',strval($siteId))) $errors['site_id']='Invalid site id';
        return [$errors, compact('name','contact','ic','company','email','party','host','hostId','purpose','siteId')];
    }

    public function createVisit($payload, $userId=null, $files=null){
        list($errors,$v)=$this->validateVisitPayload($payload);
        if(!empty($errors)) return ['success'=>false,'errors'=>$errors];
        // Ensure site exists to avoid FK violation
        $siteRows = Class_db::getInstance()->db_select('cli_site', ['site_id'=>$v['siteId']], null, '1');
        if (empty($siteRows)) {
            return ['success'=>false,'errors'=>['site_id'=>'Unknown site']];
        }
        // Resolve host via host_id if provided
        $resolvedHostName = $v['host'];
        $hostIdToStore = null;
        if(isset($v['hostId']) && $v['hostId']!==null){
            // fetch host row and ensure it exists & belongs to same site
            $hostRow = Class_db::getInstance()->db_select_single('vm_host', ['host_id'=>strval($v['hostId'])]);
            if(empty($hostRow)){
                return ['success'=>false,'errors'=>['host_id'=>'Host not found']];
            }
            if(strval($hostRow['site_id']) !== strval($v['siteId'])){
                return ['success'=>false,'errors'=>['host_id'=>'Host does not belong to provided site']];
            }
            $hostIdToStore = intval($v['hostId']);
            $resolvedHostName = $hostRow['name']; // canonical name
        }
        // Handle photo upload sources
    $relativePhotoPath = '';
    $uploadedPhotoPath = '';
    $jsonPhotoPath = '';
    // Absolute filesystem fallbacks (not stored in DB; used only for email inline)
    $uploadedPhotoAbs = '';
    $jsonPhotoAbs = '';
        try {
            // Prefer file upload via $_FILES['photo']
            if (is_array($files) && isset($files['photo']) && isset($files['photo']['tmp_name'])) {
                $errCode = isset($files['photo']['error']) ? intval($files['photo']['error']) : -1;
                $errMap = [
                    UPLOAD_ERR_OK=>'OK',
                    UPLOAD_ERR_INI_SIZE=>'INI_SIZE',
                    UPLOAD_ERR_FORM_SIZE=>'FORM_SIZE',
                    UPLOAD_ERR_PARTIAL=>'PARTIAL',
                    UPLOAD_ERR_NO_FILE=>'NO_FILE',
                    UPLOAD_ERR_NO_TMP_DIR=>'NO_TMP_DIR',
                    UPLOAD_ERR_CANT_WRITE=>'CANT_WRITE',
                    UPLOAD_ERR_EXTENSION=>'EXTENSION'
                ];
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing uploaded photo file; upload_error=' . ($errMap[$errCode] ?? strval($errCode)));
                if ($errCode !== UPLOAD_ERR_OK) {
                    // Don't bail out; continue to try JSON/base64 fallback below
                }
                $tmp = $files['photo']['tmp_name'];
                $size = isset($files['photo']['size']) ? intval($files['photo']['size']) : 0;
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'tmp_exists='.(file_exists($tmp)?'1':'0').', is_uploaded='.(function_exists('is_uploaded_file') && @is_uploaded_file($tmp)?'1':'0'));
                if ($size > 0) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Uploaded photo size: ' . $size);
                    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                    $mime = $finfo ? finfo_file($finfo, $tmp) : (mime_content_type($tmp) ?: 'application/octet-stream');
                    if ($finfo) { finfo_close($finfo); }
                    // Derive extension prefering MIME, then original filename
                    $ext = '';
                    if ($mime === 'image/jpeg' || $mime === 'image/jpg') $ext = 'jpg';
                    else if ($mime === 'image/png') $ext = 'png';
                    else if ($mime === 'image/heic' || $mime === 'image/heif') $ext = 'heic';
                    if ($ext === '' && !empty($files['photo']['name'])) {
                        $fn = strtolower($files['photo']['name']);
                        if (substr($fn, -4) === '.png') $ext = 'png';
                        else if (substr($fn, -4) === '.jpg') $ext = 'jpg';
                        else if (substr($fn, -5) === '.jpeg') $ext = 'jpg';
                        else if (substr($fn, -5) === '.heic') $ext = 'heic';
                    }
                    if ($ext !== '') {
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Determined photo extension: ' . $ext);
                        $baseDir = dirname(__DIR__, 2); // project root
                        // Ensure uploads root exists
                        $uploadsRoot = $baseDir . '/uploads';
                        if (!is_dir($uploadsRoot)) {
                            $mkRoot = @mkdir($uploadsRoot, 0775, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0775) result=' . ($mkRoot?'1':'0'));
                            if (!$mkRoot) {
                                $mkRoot2 = @mkdir($uploadsRoot, 0777, true);
                                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0777) retry result=' . ($mkRoot2?'1':'0'));
                            }
                        }
                        // Ensure uploads/vm exists
                        $uploadDir = $uploadsRoot . '/vm';
                        if (!is_dir($uploadDir)) {
                            $mk = @mkdir($uploadDir, 0775, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/vm (0775) result=' . ($mk?'1':'0'));
                            if (!$mk) {
                                $mk2 = @mkdir($uploadDir, 0777, true);
                                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/vm (0777) retry result=' . ($mk2?'1':'0'));
                            }
                        }
                        // Try to improve permissions if not writable
                        if (!is_writable($uploadDir)) { @chmod($uploadDir, 0775); }
                        if (!is_writable($uploadDir)) { @chmod($uploadDir, 0777); }
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Uploading photo to: ' . $uploadDir . ' (writable=' . (is_writable($uploadDir)?'1':'0') . ')');
                        $fname = 'vm_' . intval($v['siteId']) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest = $uploadDir . '/' . $fname;
                        $moved = @move_uploaded_file($tmp, $dest);
                        if (!$moved) { $moved = @rename($tmp, $dest); }
                        if (!$moved) {
                            $raw = @file_get_contents($tmp);
                            if ($raw !== false) { $moved = (@file_put_contents($dest, $raw) !== false); }
                        }
                        if ($moved) {
                            $uploadedPhotoPath = 'uploads/vm/' . $fname;
                            $relativePhotoPath = $uploadedPhotoPath;
                        }
                        if (!$moved) {
                            // Fallback: write to a system temp directory so we can still embed inline in email
                            $tmpRoot = rtrim(sys_get_temp_dir(), '/');
                            $tmpDest = $tmpRoot . '/' . $fname;
                            $wrote = false;
                            $raw = @file_get_contents($tmp);
                            if ($raw !== false) { $wrote = (@file_put_contents($tmpDest, $raw) !== false); }
                            if ($wrote) {
                                $uploadedPhotoAbs = $tmpDest;
                                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Photo saved to temp fallback: ' . $tmpDest);
                            } else {
                                $lastErr = error_get_last();
                                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Photo upload failed; last_error=' . ($lastErr ? ($lastErr['message'] ?? 'n/a') : 'n/a'));
                            }
                        } else {
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Photo upload succeeded');
                        }
                    }
                }
            }
            // Fallback: base64 in payload under 'photo_base64'
            if ($uploadedPhotoPath === '' && isset($payload['photo_base64']) && is_string($payload['photo_base64']) && $payload['photo_base64'] !== '') {
                $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Processing base64 photo from payload');
                $b64 = $payload['photo_base64'];
                if (strpos($b64, 'base64,') !== false) { $b64 = substr($b64, strpos($b64, 'base64,') + 7); }
                $raw = base64_decode($b64, true);
                if ($raw !== false && strlen($raw) > 0 && strlen($raw) <= 8 * 1024 * 1024) {
                    $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Decoded base64 photo size: ' . strlen($raw));
                    // naive type sniff from header
                    $sig = substr($raw, 0, 4);
                    $ext = 'jpg';
                    if (strncmp($sig, "\x89PNG", 4) === 0) { $ext = 'png'; }
                    $baseDir = dirname(__DIR__, 2);
                    // Ensure uploads root and vm dir exist
                    $uploadsRoot = $baseDir . '/uploads';
                    if (!is_dir($uploadsRoot)) {
                        $mkRoot = @mkdir($uploadsRoot, 0775, true);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0775) result=' . ($mkRoot?'1':'0'));
                        if (!$mkRoot) {
                            $mkRoot2 = @mkdir($uploadsRoot, 0777, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0777) retry result=' . ($mkRoot2?'1':'0'));
                        }
                    }
                    $uploadDir = $uploadsRoot . '/vm';
                    if (!is_dir($uploadDir)) {
                        $mk = @mkdir($uploadDir, 0775, true);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/vm (0775) result=' . ($mk?'1':'0'));
                        if (!$mk) {
                            $mk2 = @mkdir($uploadDir, 0777, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/vm (0777) retry result=' . ($mk2?'1':'0'));
                        }
                    }
                    if (!is_writable($uploadDir)) { @chmod($uploadDir, 0775); }
                    if (!is_writable($uploadDir)) { @chmod($uploadDir, 0777); }
                    $fname = 'vm_' . intval($v['siteId']) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = $uploadDir . '/' . $fname;
                    $wrote = (@file_put_contents($dest, $raw) !== false);
                    if ($wrote) {
                        $jsonPhotoPath = 'uploads/vm/' . $fname;
                        $relativePhotoPath = $jsonPhotoPath;
                    } else {
                        // Fallback to system temp
                        $tmpRoot = rtrim(sys_get_temp_dir(), '/');
                        $tmpDest = $tmpRoot . '/' . $fname;
                        if (@file_put_contents($tmpDest, $raw) !== false) {
                            $jsonPhotoAbs = $tmpDest;
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'Base64 photo saved to temp fallback: ' . $tmpDest);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // ignore photo errors; continue without attachment
            try { if(isset($this->fn_general)) { $this->fn_general->log_error(__CLASS__, __FUNCTION__, __LINE__, 'Photo save failed: ' . $e->getMessage()); } } catch(Exception $ee) {}
        }

        $insert=[
            'site_id'=>(int)$v['siteId'],
            'name'=>$v['name'],
            'contact_no'=>$v['contact'],
            'ic_no'=>$v['ic'],
            'company'=>$v['company'],
            'email'=>$v['email'],
            'party_size'=>(int)$v['party'],
            'host_name'=>$resolvedHostName,
            'purpose'=>$v['purpose'],
            'status'=>'CHECKED_IN',
            'created_via'=>isset($payload['created_via'])?$this->sanitize($payload['created_via']):'WEB_FORM',
            'created_by'=>$userId?:null
        ];
        // Optionally store photo path if column exists (pre-insert convenience, will be ensured post-insert too)
        if ($uploadedPhotoPath !== '' || $jsonPhotoPath !== '') {
            try {
                static $hasPhotoPathCol = null;
                if ($hasPhotoPathCol === null) {
                    $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='photo_path'";
                    $cols = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'c', 0);
                    $hasPhotoPathCol = !empty($cols) && intval($cols[0])>0;
                }
                if ($hasPhotoPathCol) { $insert['photo_path'] = ($uploadedPhotoPath !== '' ? $uploadedPhotoPath : $jsonPhotoPath); }
            } catch(Exception $e) { /* ignore */ }
        }
        // Add host_id column if it exists in schema (safe during transition)
        try {
            if($hostIdToStore!==null){
                static $hasHostIdCol = null;
                if($hasHostIdCol===null){
                    $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='host_id'";
                    $cols = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'c', 0);
                    $hasHostIdCol = !empty($cols) && intval($cols[0])>0;
                }
                if($hasHostIdCol){ $insert['host_id']=$hostIdToStore; }
            }
        } catch(Exception $e){ /* ignore detection errors */ }
        $id=Class_db::getInstance()->db_insert('vm_visit',$insert);

        // If JSON payload provides 'photo' (binary or base64 string), save it now under uploads/visitor/{visit_id}/
        try {
            if (isset($payload['photo']) && is_string($payload['photo']) && $payload['photo']!=='') {
                $rawStr = $payload['photo'];
                $ext = 'jpg';
                // data URL format support
                if (strpos($rawStr, 'base64,') !== false) {
                    // e.g., data:image/jpeg;base64,....
                    $header = substr($rawStr, 0, strpos($rawStr, 'base64,') );
                    if (stripos($header, 'image/png') !== false) { $ext = 'png'; }
                    else if (stripos($header, 'image/jpeg') !== false || stripos($header, 'image/jpg') !== false) { $ext = 'jpg'; }
                    $rawStr = substr($rawStr, strpos($rawStr, 'base64,') + 7);
                }
                // Try base64 decode first; if fails, treat as raw binary string
                $bytes = base64_decode($rawStr, true);
                if ($bytes === false) { $bytes = $rawStr; }
                if ($bytes !== '' && strlen($bytes) > 0) {
                    $baseDir = dirname(__DIR__, 2);
                    // Ensure uploads root and visitor dir exist
                    $uploadsRoot = $baseDir . '/uploads';
                    if (!is_dir($uploadsRoot)) {
                        $mkRoot = @mkdir($uploadsRoot, 0775, true);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0775) result=' . ($mkRoot?'1':'0'));
                        if (!$mkRoot) {
                            $mkRoot2 = @mkdir($uploadsRoot, 0777, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads root (0777) retry result=' . ($mkRoot2?'1':'0'));
                        }
                    }
                    $dir = $uploadsRoot . '/visitor/' . intval($id);
                    if (!is_dir($dir)) {
                        $mk = @mkdir($dir, 0775, true);
                        $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/visitor/{id} (0775) result=' . ($mk?'1':'0'));
                        if (!$mk) {
                            $mk2 = @mkdir($dir, 0777, true);
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'mkdir uploads/visitor/{id} (0777) retry result=' . ($mk2?'1':'0'));
                        }
                    }
                    $fname = 'photo.' . $ext;
                    $abs = $dir . '/' . $fname;
                    $wrote = (@file_put_contents($abs, $bytes) !== false);
                    if ($wrote) {
                        $jsonPhotoPath = 'uploads/visitor/' . intval($id) . '/' . $fname;
                        $relativePhotoPath = $jsonPhotoPath;
                        // Update DB photo_path
                        $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='photo_path'";
                        $cols = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'c', 0);
                        if (!empty($cols) && intval($cols[0])>0) {
                            Class_db::getInstance()->db_update('vm_visit', ['photo_path'=>$jsonPhotoPath], ['visit_id'=>strval($id)]);
                        }
                    } else {
                        // Fallback to temp for email inline only
                        $tmpRoot = rtrim(sys_get_temp_dir(), '/');
                        $tmpDest = $tmpRoot . '/visitor_' . intval($id) . '_' . $fname;
                        if (@file_put_contents($tmpDest, $bytes) !== false) {
                            $jsonPhotoAbs = $tmpDest;
                            $this->fn_general->log_debug(__CLASS__, __FUNCTION__, __LINE__, 'JSON photo saved to temp fallback: ' . $tmpDest);
                        }
                    }
                }
            } else if ($relativePhotoPath !== '') {
                // Fallback: ensure earlier saved path persists even if not set in initial insert
                $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='vm_visit' AND COLUMN_NAME='photo_path'";
                $cols = Class_db::getInstance()->db_raw_select_colm_prepared($sql, [], 'c', 0);
                if (!empty($cols) && intval($cols[0])>0) {
                    $finalPath = ($uploadedPhotoPath !== '' ? $uploadedPhotoPath : $relativePhotoPath);
                    Class_db::getInstance()->db_update('vm_visit', ['photo_path'=>$finalPath], ['visit_id'=>strval($id)]);
                }
            }
        } catch(Exception $e) { /* ignore */ }

        $emailSent = $this->tryNotifyHostByEmail(
            $v,
            $resolvedHostName,
            $uploadedPhotoAbs,
            $jsonPhotoAbs,
            $uploadedPhotoPath,
            $jsonPhotoPath,
            $relativePhotoPath
        );

        return ['success'=>true,'visit_id'=>$id,'email_sent'=>$emailSent];
    }

    private function isSmtpConfigured() {
        try {
            $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
            if ($config === false || !isset($config['smtp'])) {
                return false;
            }
            $username = $config['smtp']['m_username'] ?? ($config['smtp']['username'] ?? '');
            $password = $config['smtp']['m_password'] ?? ($config['smtp']['password'] ?? '');
            return $username !== '' && $password !== '';
        } catch (Throwable $e) {
            return false;
        }
    }

    private function logVmWarning($message) {
        try {
            if (isset($this->fn_general)) {
                $this->fn_general->log_error(__CLASS__, 'createVisit', 0, $message);
            }
        } catch (Throwable $e) { /* ignore */ }
    }

    private function tryNotifyHostByEmail($v, $resolvedHostName, $uploadedPhotoAbs, $jsonPhotoAbs, $uploadedPhotoPath, $jsonPhotoPath, $relativePhotoPath) {
        try {
            if (!$this->isSmtpConfigured()) {
                $this->logVmWarning('Host email skipped: SMTP credentials not configured');
                return false;
            }

            $recipient = '';
            if (isset($v['hostId']) && $v['hostId'] !== null) {
                $hostRowForEmail = Class_db::getInstance()->db_select_single('vm_host', ['host_id' => strval($v['hostId'])]);
                if (!empty($hostRowForEmail) && !empty($hostRowForEmail['email']) && filter_var($hostRowForEmail['email'], FILTER_VALIDATE_EMAIL)) {
                    $recipient = $hostRowForEmail['email'];
                }
            }
            if ($recipient === '') {
                $rows = Class_db::getInstance()->db_select('vm_host', ['site_id' => strval($v['siteId']), 'name' => $resolvedHostName, 'active' => '1'], 'host_id ASC', '1');
                if (!empty($rows) && !empty($rows[0]['email']) && filter_var($rows[0]['email'], FILTER_VALIDATE_EMAIL)) {
                    $recipient = $rows[0]['email'];
                }
            }
            if ($recipient === '') {
                return false;
            }

            if (!class_exists('Class_email')) {
                require_once __DIR__ . '/f_email.php';
            }
            $mailer = new Class_email();
            $safe = function ($s) {
                return htmlspecialchars(strval($s), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            };

            $siteName = '';
            try {
                $siteRow = Class_db::getInstance()->db_select_single('cli_site', ['site_id' => strval($v['siteId'])]);
                if (!empty($siteRow) && !empty($siteRow['site_name'])) {
                    $siteName = $siteRow['site_name'];
                }
            } catch (Throwable $e) { /* ignore */ }

            $subject = 'Visitor arrival notice';
            $inlinePath = '';
            if ($uploadedPhotoAbs !== '' && is_file($uploadedPhotoAbs)) {
                $inlinePath = $uploadedPhotoAbs;
            } else if ($jsonPhotoAbs !== '' && is_file($jsonPhotoAbs)) {
                $inlinePath = $jsonPhotoAbs;
            } else {
                $finalPhotoPath = ($uploadedPhotoPath !== '' ? $uploadedPhotoPath : ($jsonPhotoPath !== '' ? $jsonPhotoPath : $relativePhotoPath));
                if ($finalPhotoPath !== '') {
                    $baseDir = dirname(__DIR__, 2);
                    $abs = $baseDir . '/' . $finalPhotoPath;
                    if (is_file($abs)) {
                        $inlinePath = $abs;
                    }
                }
            }

            $photoBlock = '';
            if ($inlinePath !== '') {
                $photoBlock = '<div style="margin:10px 0; display:flex; align-items:flex-start; gap:16px;">'
                    . '<div style="border:1px solid #ddd; padding:4px; background:#f9f9f9;">'
                    . '<img src="cid:visitor_photo" alt="Visitor Photo" '
                    . 'style="display:block; width:120px; height:160px; object-fit:cover; border:0;" />'
                    . '</div>'
                    . '</div>';
            }

            $htmlBody = '<html><body>'
                . '<p>Dear ' . $safe($resolvedHostName) . ',</p>'
                . '<p>A visitor has arrived and is looking for you.</p>'
                . $photoBlock
                . '<ul>'
                . '<li><strong>Name:</strong> ' . $safe($v['name']) . '</li>'
                . '<li><strong>IC/ID:</strong> ' . $safe($v['ic']) . '</li>'
                . '<li><strong>Company:</strong> ' . $safe($v['company']) . '</li>'
                . '<li><strong>Contact:</strong> ' . $safe($v['contact']) . '</li>'
                . '<li><strong>Party size:</strong> ' . intval($v['party']) . '</li>'
                . '<li><strong>Purpose:</strong> ' . $safe($v['purpose']) . '</li>'
                . '<li><strong>Site:</strong> ' . $safe($siteName !== '' ? $siteName : $v['siteId']) . '</li>'
                . '<li><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</li>'
                . '</ul>'
                . '<p>Thank you.</p>'
                . '</body></html>';

            if ($inlinePath !== '') {
                $mailer->send_email_365_inline_image($recipient, $subject, $htmlBody, $inlinePath, 'visitor_photo', 'visitor_photo');
            } else {
                $mailer->send_email_express($recipient, $subject, $htmlBody);
            }
            return true;
        } catch (Throwable $e) {
            $this->logVmWarning('Host email send failed: ' . $e->getMessage());
            return false;
        }
    }

    public function listVisits($siteId,$limit=100){
        $cond=['site_id'=>$siteId];
        return Class_db::getInstance()->db_select('vm_visit',$cond,'arrived_at DESC',strval(intval($limit)));
    }
}
