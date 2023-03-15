<?php
class TarefaTurma extends obj2db {			
	private $codTarefaTurma;
	private $Tarefa;
	private $Turma;
	private $dataInicio;
	private $dataFim;
	private $observacao;

	function __construct() {
		$this->Tarefa = new Tarefa();
		$this->Turma = new Turma();
		parent::__construct();
	}

	function getCodTarefaTurma() {
		return $this->codTarefaTurma;
	}			

	function setCodTarefaTurma($valor) {
		$this->codTarefaTurma = $valor;
	}			

	function getTarefa() {
		return $this->Tarefa;
	}			

	function setTarefa($valor) {
		if (is_numeric($valor)) {
			$obj = new Tarefa();
			$obj->carregar($valor);
			$this->Tarefa = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Tarefa obrigat&oacute;rio(a)!");
		} else {
			$this->Tarefa = $valor;
		}
	}			

	function getTurma() {
		return $this->Turma;
	}			

	function setTurma($valor) {
		if (is_numeric($valor)) {
			$obj = new Turma();
			$obj->carregar($valor);
			$this->Turma = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Turma obrigat&oacute;rio(a)!");
		} else {
			$this->Turma = $valor;
		}
	}			

	function getDataInicio() {
		return $this->dataInicio;
	}			

	function setDataInicio($valor) {
		$this->dataInicio = $valor;
	}			

	function getDataFim() {
		return $this->dataFim;
	}			

	function setDataFim($valor) {
		$this->dataFim = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
