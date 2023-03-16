<?php 
#declare(strict_types=1);
require_once '../src/modules/obj2db/src/obj2db.php';
require_once '../src/conectdb.php';

require_once '../src/classes/acessocurso.php';
require_once '../src/classes/curso.php';
require_once '../src/classes/tipousuario.php';
require_once '../src/classes/turma.php';
require_once '../src/classes/usuario.php';


use PHPUnit\Framework\TestCase;

final class turmaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $db;
		$cmd = "DELETE FROM turma";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

		$cmd = "DELETE FROM acessocurso";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM curso";
		$tbl = $db->prepare($cmd);
		$tbl->execute();
    }

    /**
    * @covers \Turma
    */
    public function testTurma(): void
    {
        global $db;
        $codProprietario = 1;

        $curso = new Curso();
        $curso->setDescricao("Curso 01");
        $curso->setSigla("CT 01");
        $curso->setObservacao("Obs");
        $curso->proprietario($codProprietario);
        $curso->salvar();
        $codcurso = $curso->getCodCurso();

        $turma = new Turma();
        $turma->setCurso($curso);
        $turma->setDescricao("Turma 01");
        $turma->setSigla("T01");
        $turma->setObservacao("Obs 01");
        $turma->salvar();

        $codturma = $turma->getCodTurma();
		$cmd = "SELECT * FROM turma WHERE codturma = :codturma";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame((int)$codcurso, (int)$row['codcurso']);
        $this->assertSame($turma->getDescricao(), $row['descricao']);
        $this->assertSame($turma->getSigla(), $row['sigla']);
        $this->assertSame($turma->getObservacao(), $row['observacao']);


    }


}