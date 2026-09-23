<?php

require_once __DIR__ . "/auth.php";

if (
    !isset($_SESSION["usuario_perfil"]) ||
    $_SESSION["usuario_perfil"] !== "administrador"
) {

    http_response_code(403);

    die("Acesso negado. Apenas administradores podem acessar esta área.");

}