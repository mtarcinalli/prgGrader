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

	function __construct($cod=0) {
		$this->TarefaTurma = new TarefaTurma();
		$this->Usuario = new Usuario();
		parent::__construct($cod);
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

	function carregar($cod, $codUsuario=0) {
		if (! $codUsuario) {
			return parent::carregar($cod);
		} else {
			$this->clear();
			$this->select("codtarefaturmausuario");
			$this->where("codtarefaturma = $cod AND codusuario = $codUsuario");
			$this->listar();
			$row = $this->retornar();
			if ($row) {
				parent::carregar($row["codtarefaturmausuario"]);
				return true;
			}
			return false;
		}
	}

	function salvar($forcarInsert=false) {
		$inserir = ! $this->codTarefaTurmaUsuario;
		parent::salvar($forcarInsert);
		if ($inserir) {
			$codcurso = $this->getTarefaTurma()->getTurma()->getCurso()->getCodCurso();
			$codturma = $this->getTarefaTurma()->getTurma()->getCodTurma();
			$codtarefaturma = $this->getTarefaTurma()->getCodTarefaTurma();
			$codtarefaturmausuario = $this->getCodTarefaTurmaUsuario();
			$cmd = "mkdir -p ../uploads/CURSO$codcurso/TURMA$codturma/TTURMA$codtarefaturma/TTALUNO$codtarefaturmausuario";
			$output = shell_exec($cmd);
		}

	}

	function corrigir($tarefaTurma, $usuario, $arquivo) {
		if (! $this->carregar($tarefaTurma->getCodTarefaTurma(), $usuario->getCodUsuario())) {
			$this->setTarefaTurma($tarefaTurma);
			$this->setUsuario($usuario);
			$this->setEntregas(0);
			$this->setNotafinal(null);
			$this->salvar();
		}


		$codtarefaturma = $tarefaTurma->getCodTarefaTurma();
		$codturma = $tarefaTurma->getTurma()->getCodTurma();
		$codcurso = $tarefaTurma->getTurma()->getCurso()->getCodCurso();
		$codtarefaturmausuario = $this->getCodTarefaTurmaUsuario();
		$codtarefa = $tarefaTurma->getTarefa()->getCodTarefa();;
		$codcorretor = $tarefaTurma->getTarefa()->getCorretor()->getCodCorretor();

		$uploaddir = "../uploads/CURSO$codcurso/TURMA$codturma/TTURMA$codtarefaturma/TTALUNO$codtarefaturmausuario/";
		$uploadfile = $uploaddir . "arquivo.zip";

		$files = glob($uploaddir . "*");
		foreach($files as $file){
			if(is_file($file)) {
				unlink($file);
			}
		}

		if (substr($arquivo, -8) == "temp.zip") {
			rename($arquivo, $uploadfile);
		} else {
			if (!move_uploaded_file($arquivo, $uploadfile)) {
				throw new Exception("Erro ao enviar arquivo!");
			}
		}


		$this->setDataEntrega(date("Y-m-d"));
		$this->entregas += 1;
		if (! $this->notafinal) {
			$this->notafinal = null;
		}

		$cmd = "cd $uploaddir && ls && echo '---' && " .
					"ls && " .
					"unzip -j arquivo.zip && " .
					"cp -a ../../../../TAREFAS/T" . $codtarefa .  "/solution/* . && " .
					"cp -a ../../../../CORRETORES/CORRETOR" . $codcorretor .  "/corretor/* . && " .
					"bash ./grader.sh";
		$output = trim(shell_exec($cmd));
		$this->nota = intval(trim(substr($output, strrpos($output, "\n"), -1)));
		$this->salvar();
	}

}
