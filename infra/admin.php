<?php

require_once __DIR__ . "/auth.php";

if ($_SESSION["usuario_perfil"] !== "administrador") {
    http_response_code(403);
    die("Acesso negado.");
}