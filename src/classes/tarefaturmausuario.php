<?php
class TarefaTurmaUsuario extends obj2db {			
	private $codTarefaTurmaUsuario;
	private $TarefaTurma;
	private $Usuario;
	private $dataEntrega;
	private $entregas;
	private $resultados;
	private $nota;
	private $notafinal;
	private $observacao;

	function __construct() {
		$this->TarefaTurma = new TarefaTurma();
		$this->Usuario = new Usuario();
		parent::__construct();
	}

	function getCodTarefaTurmaUsuario() {
		return $this->codTarefaTurmaUsuario;
	}			

	function setCodTarefaTurmaUsuario($valor) {
		$this->codTarefaTurmaUsuario = $valor;
	}			

	function getTarefaTurma() {
		return $this->TarefaTurma;
	}			

	function setTarefaTurma($valor) {
		if (is_numeric($valor)) {
			$obj = new Tarefaturma();
			$obj->carregar($valor);
			$this->TarefaTurma = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Tarefaturma obrigat&oacute;rio(a)!");
		} else {
			$this->TarefaTurma = $valor;
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

	function getDataEntrega() {
		return $this->dataEntrega;
	}			

	function setDataEntrega($valor) {
		$this->dataEntrega = $valor;
	}			

	function getEntregas() {
		return $this->entregas;
	}			

	function setEntregas($valor) {
		$this->entregas = $valor;
	}			

	function getResultados() {
		return $this->resultados;
	}			

	function setResultados($valor) {
		$this->resultados = $valor;
	}			

	function getNota() {
		return $this->nota;
	}			

	function setNota($valor) {
		$this->nota = $valor;
	}			

	function getNotafinal() {
		return $this->notafinal;
	}			

	function setNotafinal($valor) {
		$this->notafinal = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
