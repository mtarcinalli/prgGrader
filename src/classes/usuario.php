<?php
class Usuario extends obj2db {			
	private $codUsuario;
	private $TipoUsuario;
	private $nome;
	private $email;
	private $senha;
	private $alterasenha;
	private $observacao;

	function __construct() {
		$this->TipoUsuario = new Tipousuario();
		parent::__construct();
	}

	function getCodUsuario() {
		return $this->codUsuario;
	}			

	function setCodUsuario($valor) {
		$this->codUsuario = $valor;
	}			

	function getTipoUsuario() {
		return $this->TipoUsuario;
	}			

	function setTipoUsuario($valor) {
		if (is_numeric($valor)) {
			$obj = new Tipousuario();
			$obj->carregar($valor);
			$this->TipoUsuario = $obj;
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Tipousuario obrigat&oacute;rio(a)!");
		} else {
			$this->TipoUsuario = $valor;
		}
	}			

	function getNome() {
		return $this->nome;
	}			

	function setNome($valor) {
		$this->nome = $valor;
	}			

	function getEmail() {
		return $this->email;
	}			

	function setEmail($valor) {
		$this->email = $valor;
	}			

	function getSenha() {
		return $this->senha;
	}			

	function setSenha($valor) {
		$this->senha = $valor;
	}			

	function getAlterasenha() {
		return $this->alterasenha;
	}			

	function setAlterasenha($valor) {
		$this->alterasenha = $valor;
	}			

	function getObservacao() {
		return $this->observacao;
	}			

	function setObservacao($valor) {
		$this->observacao = $valor;
	}			

}
