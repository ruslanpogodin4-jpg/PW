mkdir data.fix
ls dbdata/ | awk '{ print "./dbtool -r -s dbdata/"$1" -d data.fix/"$1 }' | /bin/sh
rm -fr dbdata
rm -fr dblogs/*
mv data.fix dbdata
