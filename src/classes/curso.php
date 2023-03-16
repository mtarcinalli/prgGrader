<?php
class Curso extends obj2db {			
	private $codCurso;
	private $descricao;
	private $sigla;
	private $observacao;
	private $proprietario;

	function __construct($cod = 0) {
		$this->proprietario = new Usuario();
		parent::__construct($cod);
	}

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

	function proprietario($valor) {
		if (is_numeric($valor)) {
			$this->proprietario->carregar($valor);
		} elseif (! is_object($valor) || $valor == "") {
			throw new Exception("Proprietário obrigat&oacute;rio(a)!");
		} else {
			$this->proprietario = $valor;
		}
	}

	function listaAcessos() {
		$acesso = new AcessoCurso();
        $acesso->clear();
        $acesso->limit(0);
        $acesso->select("codusuario");
		$acesso->where("codcurso = $this->codCurso");
        $acesso->listar();
        while ($row = $acesso->retornar()) {
            $list[] = $row["codusuario"];
        }
        $acesso->clear();
        return($list);

	}

	function salvar($forcarInsert=false) {
		if (! $this->codCurso && ! $this->proprietario->getCodUsuario()) {
			throw new Exception("Proprietário obrigat&oacute;rio(a)!");
		}
		$insereProprietario = ! $this->codCurso;
		parent::salvar($forcarInsert=false);
		if ($insereProprietario) {
			$acesso = new AcessoCurso();
			$acesso->setCurso($this);
			$acesso->setUsuario($this->proprietario);
			$acesso->salvar();
		}
	}
}
