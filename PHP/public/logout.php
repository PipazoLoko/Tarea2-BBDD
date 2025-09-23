<?php
require_once __DIR__ . '/../config/bootstrap.php';
logoutUser();
session_name($config['session_name']);
session_start();
flash('success', 'Sesion cerrada correctamente.');
redirect('login.php');
