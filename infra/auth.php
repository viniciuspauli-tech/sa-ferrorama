<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario_id"])) {

    /*
     * Monta o caminho do login a partir da pasta /public/,
     * funcionando em qualquer subpasta (usuarios, rotas, trem...).
     */
    $script = $_SERVER["SCRIPT_NAME"];
    $pos = strpos($script, "/public/");
    $base = $pos !== false ? substr($script, 0, $pos) : "";

    header("Location: " . $base . "/public/sigin.php");
    exit;

}
