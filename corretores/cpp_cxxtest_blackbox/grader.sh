#/bin/bash
cxxtestgen --error-printer --have-eh -o runner01.cpp solution.h 2>&1 && g++ -o runner01 runner01.cpp 2>&1 && echo '====' && timeout 8s ./runner01 2>&1 | grep "Success rate\|.OK!"