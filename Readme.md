
### Install project 

(You should have docker and ddev on your computer. Please look at https://ddev.readthedocs.io/en/stable/)
1. Clone project from Git repo https://github.com/varahi/smcentr
2. Rename ```.env.dist``` to ```.env```
3. Run ```ddev start```
4. Import example database ```ddev import-db --src=dump.sql.gz```
5. Enter inside ddev ssh 
```ddev ssh``` and run ```composer install```
6. Go to link https://smcentr.su.ddev.site/ and use
7. Build js 
```shell
   yarn install
   yarn build
   yarn dev
```

### Short ddev commands

```shell
ddev poweroff

ddev pause

ddev stop

ddev start

ddev describe
```

### Export DB
```shell
ddev export-db --target-db=db -f database.sql.gz
```

### Import DB
```shell
ddev import-db --src=database.sql.gz
```

### RabbitMQ access
http://smcentr.su.ddev.site:15672/
http://localhost:15672/
login: rabbitmq
pass: rabbitmq