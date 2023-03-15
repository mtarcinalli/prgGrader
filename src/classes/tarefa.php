<?php
class Tarefa extends obj2db {			
	private $codTarefa;
	private $plugin;
	private $descricao;
	private $sigla;
	private $instrucoes;
	private $observacao;

	function __construct() {
		$this->plugin = new Plugin();
		parent::__construct();
	}

	function getCodTarefa() {
		return $this->codTarefa;
	}			

	function setCodTarefa($valor) {
		$this->codTarefa = $valor;
	}			

	function getPlugin() {
		return $this->plugin;
	}			

	function setPlugin($valor) {
		if (is_numeric($valor)) {
			$obj = new Plugin();
			$obj->carregar($valor);
			$this->plugin = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Plugin obrigat&oacute;rio(a)!");
		} else {
			$this->plugin = $valor;
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
