FROM debian:latest

LABEL maintainer="Mateus Machado"

RUN apt-get -y update && apt-get upgrade -y

RUN apt-get install apache2 php php-pgsql postgresql -y

ADD ./www /var/www/html

EXPOSE 80

COPY ./db/postgres.sql /postgres/

USER postgres

RUN    /etc/init.d/postgresql start &&\
    psql --command "CREATE USER pgrader WITH SUPERUSER PASSWORD 'pgrader';" &&\
    createdb -O pgrader pgrader &&\
    psql -d pgrader -f /postgres/postgres.sql

# Adjust PostgreSQL configuration so that remote connections to the
# database are possible.
RUN echo "host all  all    0.0.0.0/0  md5" >> /etc/postgresql/13/main/pg_hba.conf

# And add ``listen_addresses`` to ``/etc/postgresql/13/main/postgresql.conf``
RUN echo "listen_addresses='*'" >> /etc/postgresql/13/main/postgresql.conf

# Expose the PostgreSQL port
EXPOSE 5432

# Add VOLUMEs to allow backup of config, logs and databases
VOLUME  ["/etc/postgresql", "/var/log/postgresql", "/var/lib/postgresql"]

# Set the default command to run when starting the container

USER root


#USER postgres

CMD apachectl -k start ; su - postgres -c "/usr/lib/postgresql/13/bin/postgres -D /var/lib/postgresql/13/main -c config_file=/etc/postgresql/13/main/postgresql.conf"
#CMD su - postgres -c "/usr/lib/postgresql/13/bin/postgres -D /var/lib/postgresql/13/main -c config_file=/etc/postgresql/13/main/postgresql.conf & " ; apachectl -D FOREGROUND
