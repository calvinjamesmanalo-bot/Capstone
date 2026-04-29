<?php

if (!function_exists('record_log')) {
    function record_log($action, $module, $description = null, $status = 'success') {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'status' => $status,
            'ip_address' => request()->ip(),
        ]);
    }
}