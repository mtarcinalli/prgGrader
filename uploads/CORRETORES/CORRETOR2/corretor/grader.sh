#!/bin/bash
export CLASSPATH=.:$CLASSPATH:junit/junit-4.13.2.jar:junit/hamcrest-core-1.3.jar
echo "===="
javac *.java
output=`java org.junit.runner.JUnitCore Solution`
echo -ne "$output"
res=`echo -ne "$output" | grep "Tests run:\|OK ("`
if [[ "$res" == *"OK ("* ]] ; then
        nota="100%"
elif [[ "$res" == *"Tests run:"* ]] ; then
        run=`echo -ne "$output" | grep "Tests run" | cut -d, -f1 | cut -d: -f2`
        failures=`echo -ne "$output" | grep "Tests run" | cut -d, -f2 | cut -d: -f2`
        nota=`echo "100 - $failures / $run * 100" | bc -l | awk -F\, '{print int($1+0.5)"%"}'`
else
        nota="0%"
fi
echo -ne "\n\n$nota"