<?php
require_once 'includes/config.php';
require_once 'includes/gmail_functions.php';

// This script should be run via cron job
processEmailQueue();
