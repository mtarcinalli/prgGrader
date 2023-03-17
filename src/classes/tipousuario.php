<?php
class TipoUsuario extends obj2db {
	private $codTipoUsuario;
	private $descricao;

	function getCodTipoUsuario() {
		return $this->codTipoUsuario;
	}			

	function setCodTipoUsuario($valor) {
		$this->codTipoUsuario = $valor;
	}			

	function getDescricao() {
		return $this->descricao;
	}			

	function setDescricao($valor) {
		$this->descricao = $valor;
	}			

}
