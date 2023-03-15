<?php
class Curso extends obj2db {			
	private $codCurso;
	private $descricao;
	private $sigla;
	private $observacao;

	function getCodCurso() {
		return $this->codCurso;
	}			

	function setCodCurso($valor) {
		$this->codCurso = $valor;
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
