<?php
class AcessoCurso extends obj2db {			
	private $codAcessoCurso;
	private $Curso;
	private $Usuario;

	function __construct() {
		$this->Curso = new Curso();
		$this->Usuario = new Usuario();
		parent::__construct();
	}

	function getCodAcessoCurso() {
		return $this->codAcessoCurso;
	}			

	function setCodAcessoCurso($valor) {
		$this->codAcessoCurso = $valor;
	}			

	function getCurso() {
		return $this->Curso;
	}			

	function setCurso($valor) {
		if (is_numeric($valor)) {
			$obj = new Curso();
			$obj->carregar($valor);
			$this->Curso = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Curso obrigat&oacute;rio(a)!");
		} else {
			$this->Curso = $valor;
		}
	}			

	function getUsuario() {
		return $this->Usuario;
	}			

	function setUsuario($valor) {
		if (is_numeric($valor)) {
			$obj = new Usuario();
			$obj->carregar($valor);
			$this->Usuario = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Usuario obrigat&oacute;rio(a)!");
		} else {
			$this->Usuario = $valor;
		}
	}			

}
