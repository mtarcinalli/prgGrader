<?php declare(strict_types=1);
require_once '../src/modules/obj2db/src/obj2db.php';
require_once '../src/conectdb.php';
require_once '../src/classes/curso.php';
require_once '../src/classes/turma.php';

use PHPUnit\Framework\TestCase;

final class prgGraderTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $db;
		$cmd = "DELETE FROM turma";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

        $cmd = "DELETE FROM curso";
		$tbl = $db->prepare($cmd);
		$tbl->execute();


    }

    /**
    * @covers \Curso
    */
    public function testCurso(): void
    {
        global $db;
        $curso1 = new Curso();
        $curso1->setDescricao("Curso 01");
        $curso1->setSigla("CT 01");
        $curso1->setObservacao("Obs");
        $curso1->salvar();
        $curso2 = new Curso();
        $curso2->carregar($curso1->getCodCurso());
        $this->assertSame($curso1->getDescricao(), $curso2->getDescricao());
        $this->assertSame($curso1->getSigla(), $curso2->getSigla());
        $this->assertSame($curso1->getObservacao(), $curso2->getObservacao());

		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso1->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($curso1->getDescricao(), $row['descricao']);
        $this->assertSame($curso1->getSigla(), $row['sigla']);
        $this->assertSame($curso1->getObservacao(), $row['observacao']);

        $curso1->setDescricao("Curso 001");
        $curso1->setSigla("CT 001");
        $curso1->setObservacao("Observação");
        $curso1->salvar();
		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso1->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($curso1->getDescricao(), $row['descricao']);
        $this->assertSame($curso1->getSigla(), $row['sigla']);
        $this->assertSame($curso1->getObservacao(), $row['observacao']);

        $turma = new Turma();
        $turma->setCurso($curso1);
        $turma->setDescricao("Turma 01");
        $turma->setSigla("T01");
        $turma->setObservacao("Obs 01");
        $turma->salvar();




        $curso1->excluir();
		$cmd = "SELECT * FROM curso WHERE codcurso = :codcurso";
		$tbl = $db->prepare($cmd);
        $tbl->bindValue(':codcurso', $curso2->getCodCurso(), PDO::PARAM_INT);
		$tbl->execute();
		$row = $tbl->fetch();
        $this->assertSame($row, false);
        #$this->assertSame($curso1->getSigla(), $row['sigla']);
        #$this->assertSame($curso1->getObservacao(), $row['observacao']);
    }

    /**
    * @covers \Turma
    */
    public function testTurma(): void
    {
        global $db;
        $curso = new Curso();
        $curso->setDescricao("Curso 01");
        $curso->setSigla("CT 01");
        $curso->setObservacao("Obs");
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