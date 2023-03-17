<?php
class Corretor extends obj2db {			
	private $codCorretor;
	private $descricao;
	private $retorno;
	private $observacao;

	function getCodCorretor() {
		return $this->codCorretor;
	}			

	function setCodCorretor($valor) {
		$this->codCorretor = $valor;
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
