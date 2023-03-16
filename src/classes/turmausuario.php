<?php
class Turmausuario extends obj2db {			
	private $codTurmaUsuario;
	private $turma;
	private $usuario;

	function __construct($cod=0) {
		$this->turma = new Turma();
		$this->usuario = new Usuario();
		parent::__construct($cod);
	}

	function getCodTurmaUsuario() {
		return $this->codTurmaUsuario;
	}			

	function setCodTurmaUsuario($valor) {
		$this->codTurmaUsuario = $valor;
	}			

	function getTurma() {
		return $this->turma;
	}			

	function setTurma($valor) {
		if (is_numeric($valor)) {
			$obj = new Turma();
			$obj->carregar($valor);
			$this->turma = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Turma obrigat&oacute;rio(a)!");
		} else {
			$this->turma = $valor;
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
