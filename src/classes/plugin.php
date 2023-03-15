<?php
class Plugin extends obj2db {			
	private $codPlugin;
	private $descricao;
	private $retorno;
	private $observacao;

	function getCodPlugin() {
		return $this->codPlugin;
	}			

	function setCodPlugin($valor) {
		$this->codPlugin = $valor;
	}			

	function getDescricao() {
		return $this->descricao;
	}			

	function setDescricao($valor) {
		$this->descricao = $valor;
	}			

	function getRetorno() {
		return $this->retorno;
	}			

	function setRetorno($valor) {
		$this->retorno = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
