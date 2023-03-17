<?php
class Tarefa extends obj2db {			
	private $codTarefa;
	private $corretor;
	private $descricao;
	private $sigla;
	private $instrucoes;
	private $observacao;

	function __construct($cod=0) {
		$this->corretor = new Corretor();
		parent::__construct($cod);
	}

	function getCodTarefa() {
		return $this->codTarefa;
	}			

	function setCodTarefa($valor) {
		$this->codTarefa = $valor;
	}			

	function getCorretor() {
		return $this->corretor;
	}			

	function setCorretor($valor) {
		if (is_numeric($valor)) {
			$obj = new Corretor();
			$obj->carregar($valor);
			$this->corretor = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Corretor obrigat&oacute;rio(a)!");
		} else {
			$this->corretor = $valor;
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

	function getInstrucoes() {
		return $this->instrucoes;
	}			

	function setInstrucoes($valor) {
		$this->instrucoes = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
