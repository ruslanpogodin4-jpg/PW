mkdir gastondb/data.fix
ls gastondb/dbdata/ | awk '{ print "./dbtool -r -s gastondb/dbdata/"$1" -d gastondb/data.fix/"$1 }' | /bin/sh
rm -fr gastondb/dbdata
rm -fr gastondb/dblogs/*
mv gastondb/data.fix gastondb/dbdata
