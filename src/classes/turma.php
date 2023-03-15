<?php
class Turma extends obj2db {			
	private $codTurma;
	private $curso;
	private $descricao;
	private $sigla;
	private $observacao;

	function __construct() {
		$this->curso = new Curso();
		parent::__construct();
	}

	function getCodTurma() {
		return $this->codTurma;
	}			

	function setCodTurma($valor) {
		$this->codTurma = $valor;
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

	function getDescricao() {
		return $this->descricao;
	}			

	function setDescricao($valor) {
		$this->descricao = $valor;
	}			

	function getSigla() {
		return $this->sigla;
	}			

	function setSigla($valor) {
		$this->sigla = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
