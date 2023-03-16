<?php
class AcessoCurso extends obj2db {			
	private $codAcessoCurso;
	private $curso;
	private $usuario;

	function __construct($cod=0) {
		$this->curso = new Curso();
		$this->usuario = new Usuario();
		parent::__construct($cod);
	}

	function getCodAcessoCurso() {
		return $this->codAcessoCurso;
	}			

	function setCodAcessoCurso($valor) {
		$this->codAcessoCurso = $valor;
	}			

	function getCurso() {
		return $this->curso;
	}			

	function setCurso($valor) {
		if (is_numeric($valor)) {
			$obj = new Curso();
			$obj->carregar($valor);
			$this->curso = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Curso obrigat&oacute;rio(a)!");
		} else {
			$this->curso = $valor;
		}
	}			

	function getUsuario() {
		return $this->usuario;
	}			

	function setUsuario($valor) {
		if (is_numeric($valor)) {
			$obj = new Usuario();
			$obj->carregar($valor);
			$this->usuario = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Usuario obrigat&oacute;rio(a)!");
		} else {
			$this->usuario = $valor;
		}
	}			

}
