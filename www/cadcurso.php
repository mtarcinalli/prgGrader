<?php
require_once("header.php");

class Form extends Form0 {
}

$obj = new Curso();
$frm = new Form($obj);
$frm->titulo = "Cursos";
$frm->tituloSingular = "Curso";
$frm->setAtributosDesc("Descricao", "Descrição");
$frm->setAtributosDesc("Observacao", "Observação");
$frm->action();
require_once("footer.php");