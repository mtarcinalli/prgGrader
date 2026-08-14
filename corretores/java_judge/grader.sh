#!/bin/bash

set -euo pipefail

# Uso: grader.sh [submission_dir]
SUBMISSION_DIR="${1:-.}"
IN_DIR="$SUBMISSION_DIR/in"
OUT_DIR="$SUBMISSION_DIR/out"
TIMEOUT_CMD="timeout"
TIMEOUT_SECS=5

WORKDIR=$(mktemp -d /tmp/grader.XXXX)
trap 'rm -rf "$WORKDIR"' EXIT

echo "Grader: trabalhando em $SUBMISSION_DIR"

# Copy java files
shopt -s nullglob
JAVA_FILES=("$SUBMISSION_DIR"/*.java)
if [ ${#JAVA_FILES[@]} -eq 0 ]; then
	echo "ERROR: nenhum arquivo .java encontrado em $SUBMISSION_DIR"
	exit 1
fi
cp "$SUBMISSION_DIR"/*.java "$WORKDIR"/
cd "$WORKDIR"

# Compile
echo "Compilando..."
if ! javac *.java 2>compile.err; then
	echo "COMPILE_ERROR"
	cat compile.err
	exit 2
fi

# Detectar classe com main
MAIN_CLASS=""
for f in *.java; do
	if grep -q "public static void main" "$f"; then
		MAIN_CLASS="${f%.java}"
		break
	fi
done
if [ -z "$MAIN_CLASS" ]; then
	# fallback: usar primeira classe compilada
	first_class=$(ls *.class 2>/dev/null | head -n1 || true)
	if [ -n "$first_class" ]; then
		MAIN_CLASS="${first_class%.class}"
	else
		echo "ERROR: nenhuma classe compilada encontrada"
		exit 3
	fi
fi

echo "Usando classe principal: $MAIN_CLASS"

# Run tests
if [ ! -d "$IN_DIR" ]; then
	echo "WARNING: diretório de entrada $IN_DIR não existe ou vazio"
	exit 4
fi

inputs=("$IN_DIR"/input*.txt)
total=0
passed=0

normalize() {
	sed 's/\r$//' "$1" | sed 's/[[:space:]]\+$//' > "$1.norm"
}

for in_file in "${inputs[@]}"; do
	[ -e "$in_file" ] || continue
	((total++))
	base=$(basename "$in_file")
	out_name=${base/input/output}
	expected="$OUT_DIR/$out_name"
	actual="$WORKDIR/out_${total}.txt"

	if ! $TIMEOUT_CMD ${TIMEOUT_SECS}s java -cp "$WORKDIR" "$MAIN_CLASS" < "$in_file" > "$actual" 2>"$WORKDIR/run_${total}.err"; then
		echo "Test $total: RUNTIME_ERROR"
		echo "Stderr:"; sed -n '1,200p' "$WORKDIR/run_${total}.err"
		continue
	fi

	if [ ! -f "$expected" ]; then
		echo "Test $total: expected file $expected not found -> counted as wrong"
		continue
	fi

	normalize "$expected"
	normalize "$actual"

	if cmp -s "$expected.norm" "$actual.norm"; then
		echo "Test $total: OK"
		((passed++))
	else
		echo "Test $total: FAIL"
		echo "--- expected (first 200 chars) ---"
		head -c 200 "$expected" || true
		echo
		echo "--- actual (first 200 chars) ---"
		head -c 200 "$actual" || true
		echo
	fi
done

if [ $total -eq 0 ]; then
	echo "Nenhum caso de teste encontrado em $IN_DIR"
	exit 5
fi

percent=$(( passed * 100 / total ))
echo "\nResumo: $passed / $total testes corretos -> $percent%"

exit 0