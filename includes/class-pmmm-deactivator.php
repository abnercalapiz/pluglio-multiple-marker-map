<?php
/**
 * Plugin deactivation class
 */

class PMMM_Deactivator {
    
    public static function deactivate() {
        // Flush rewrite rules to remove our endpoint
        flush_rewrite_rules();
    }
}