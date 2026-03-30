Cria Installation Manual

These institutions are specific to installing Cria with a docker using a Virtual Machine provided by UIT SMS.

# Virtual Machine (VM) Requirements

VCPU: 4

RAM: 16 GB

Drive: 1 TB Mount to /data

Important, use an attached drive (sdb) and not an NFS drive.

OS: Latest version of Ubuntu

# Docker Installation

If docker has been installed using the predefined repositories in Ubuntu (sudo apt install), Male sure to remove the installation and all related folders.

## [Uninstall Docker Engine](https://docs.docker.com/engine/install/ubuntu/#uninstall-docker-engine)<sup>[\[1\]](#footnote-2361)</sup>

1. Uninstall the Docker Engine, CLI, containerd, and Docker Compose packages:  
   <br/>```sudo apt-get purge docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin docker-ce-rootless-extras```


2. Images, containers, volumes, or custom configuration files on your host aren't automatically removed. To delete all images, containers, and volumes:  
   <br/>`sudo rm -rf /var/lib/docker`
   <br />`sudo rm -rf /var/lib/containerd`

sudo rm –rf /etc/docker

## Install from package<sup>[\[2\]](#footnote-31270)</sup>

If you can't use Docker's apt repository to install Docker Engine, you can download the deb file for your release and install it manually. You need to download a new file each time you want to upgrade Docker Engine.

1. Go to <https://download.docker.com/linux/ubuntu/dists/>
2. Select your Ubuntu version in the list.
3. Go to pool/stable/ and select the applicable architecture (amd64, armhf, arm64, or s390x).
4. Download the following deb files for the Docker Engine, CLI, containerd, and Docker Compose packages:
5. containerd.io_&lt;version&gt;\_&lt;arch&gt;.deb
6. docker-ce_&lt;version&gt;\_&lt;arch&gt;.deb
7. docker-ce-cli_&lt;version&gt;\_&lt;arch&gt;.deb
8. docker-buildx-plugin_&lt;version&gt;\_&lt;arch&gt;.deb
9. docker-compose-plugin_&lt;version&gt;\_&lt;arch&gt;.deb
10. Install the .deb packages. Update the paths in the following example to where you downloaded the Docker packages.  
```
sudo dpkg -i ./containerd.io_<version>\_<arch>.deb \\
    ./docker-ce_<version>\_<arch>.deb \\  
    ./docker-ce-cli_<version>\_<arch>.deb \\  
    ./docker-buildx-plugin_<version>\_<arch>.deb \\  
    ./docker-compose-plugin_<version>\_<arch>.deb
```

The Docker daemon starts automatically.

Verify that the Docker Engine installation is successful by running the hello-world image.
````
sudo service docker start  
sudo docker run hello-world 
```` 
<br/>This command downloads a test image and runs it in a container. When the container runs, it prints a confirmation message and exits.

You have now successfully installed and started Docker Engine.

## Grant Docker privileges to non-sudo users

Docker only works with sudo. To allow non-sudo users to run docker commands, add the user(s) to the docker group.
```
sudo usermod -aG docker $USER
```

If the docker group does not exist on the system, create it
```
sudo groupadd docker
```

## Move Docker default folder to the attached drive

The default drive space on UIT VMs (64GB) is too small to host docker. It is essential that the default docker folder (/var/lib/docker) be set in the attached drive.

1. Create a folder within the attached drive  
```
sudo mkdir /data/docker_data
```

2. Stop the docker service  
```
sudo service docker stop
```

3. Rsync the default docker folder to /data/docker_data  
```
rsync –avz /var/liv/docker /data/docker_data
```

4. Create daemon.json and add path to new location. Nano is used here, but any editor will work.  
```
sudo nano /etc/docker/daemon.json
```

```
{
   "data-root": "/data/docker_data/docker"  
}
```
   <br/>Save the file ctrl-x and Y when prompted.

5. Start docker engine  
```
sudo service docker start
```
# Cria Installation

## Prepare docker-compose.yml file
1. Create a folder named cria-prod and cd into it.
```
mkdir cria-prod
cd cria-prod
```
2. Copy past the following code into a file named docker-compose.yml
```
  version: '3.7'
  services:
    # Cria-Front backend redis    
    redis:
      image: 'docker.io/redis:latest'
      environment:
        REDIS_ARGS: "--requirepass root"
      ports:
        - '127.0.0.1:6379:6379'
      volumes:
        - ./redis_data/redis.conf:/usr/local/etc/redis/redis.conf
    # Cria-Front Moodle
    cria:
      platform: linux/amd64
      image: 'docker.io/moodlehq/moodle-php-apache:8.1'
      ports:
        - '127.0.0.1:8080:80'
      volumes:
        - './html:/var/www/html:cached'
        - './moodledata:/var/www/moodledata:cached'
        - './log:/var/log/apache2:cached'
      depends_on:
        - redis
    # Cria-Front CriaScraper
    criascraper:
      image: amerkurev/scrapper:latest
      restart: unless-stopped
      ports:
        - "127.0.0.1:3000:3000"
      volumes:
        - "./user_data:/home/user/user_data"
        - "./user_scripts:/home/user/user_scripts"
    # Elasticsearch
    elasticsearch:
      image: docker.io/elasticsearch:8.13.4
      environment:
        discovery.type: single-node
        ES_JAVA_OPTS: "-Xms512m -Xmx512m"
        ELASTIC_PASSWORD: ${ELASTIC_PASSWORD}
      ports:
        - "127.0.0.1:9200:9200"
      volumes:
        - ./elasticsearch_data:/usr/share/elasticsearch/data:cached
      healthcheck:
        test: ["CMD-SHELL", "curl -fsS -u elastic:$$ELASTIC_PASSWORD 'http://localhost:9200/_cluster/health?wait_for_status=yellow&timeout=1s' >/dev/null || exit 1"]
        start_period: 10s
        interval: 5s
        timeout: 5s
        retries: 20
    # MinIO for Ragflow
    minio:
      image: minio/minio:RELEASE.2025-09-07T16-13-09Z
      command: server /data --console-address ":9001"
      ports:
        - "127.0.0.1:9000:9000"
        - "127.0.0.1:9001:9001"
      volumes:
        - ./minio_data:/data
    # Ragflow
    ragflow:
      image: infiniflow/ragflow:v0.22.1
      ports:
        - "127.0.0.1:8080:9380"
        - "127.0.0.1:9381:80"
      depends_on:
        - minio
        - elasticsearch
      healthcheck:
        test: ["CMD", "curl", "-f", "http://ragflow:9380/v1/system/healthz"]
        start_period: 1s
        interval: 5s
        timeout: 5s
        retries: 15
    # Cria-Back Criadex
    criadex:
      image: uitadmin/criadex:latest
      ports:
        - "127.0.0.1:25574:25574"
      environment:
        ENV_PATH: "./env/docker.env"
      volumes:
        - ./criadex_data:/home/cria/env
      depends_on:
        elasticsearch:
          condition: service_healthy
        ragflow:
          condition: service_healthy
      healthcheck:
        test: [ "CMD", "curl", "-f", "http://criadex:25574/health_check" ]
        start_period: 1s
        interval: 5s
        timeout: 5s
        retries: 5
    # Cria-Back Criabot
    criabot:
      image: uitadmin/criabot:latest
      ports:
        - "127.0.0.1:25575:25575"
      environment:
        ENV_PATH: "./env/docker.env"
      volumes:
        - ./criabot_data:/home/cria/env
      depends_on:
        redis:
          condition: service_started
        criadex:
          condition: service_healthy
      healthcheck:
        test: [ "CMD", "curl", "-f", "http://criabot:25575/health_check" ]
        start_period: 1s
        interval: 5s
        timeout: 5s
        retries: 5
    # Cria-Back CriaParse
    criaparse:
      image: uitadmin/criaparse:v0.1.1
      ports:
        - "127.0.0.1:25576:25576"
      environment:
        ENV_PATH: "./env/docker.env"
      volumes:
        - ./criaparse_data:/home/cria/env
      depends_on:
        criadex:
          condition: service_healthy
    # Cria-Back Embed API
    criaembed-api:
      image: uitadmin/criaembed-api:v0.2.6
      ports:
        - "127.0.0.1:3003:3003"
      environment:
        ENV_PATH: "./env/docker.env"
      volumes:
        - ./criaembed-api_data:/home/cria/env
      depends_on:
        criabot:
          condition: service_healthy
    # Cria-Back Embed App
    criaembed-app:
      image: uitadmin/criaembed-app:v0.2.4
      ports:
        - "127.0.0.1:4000:4000"
      environment:
        ENV_PATH: "./env/docker.env"
      volumes:
        - ./criaembed-app_data:/home/cria/env
      depends_on:
        criabot:
          condition: service_healthy
```
3. Start the containers
```
docker-compose up -d
```
This will create the folders required for various configurations.
When you first run the docker-compose up command, it will download the images and create the containers. 
This process may take a few minutes. Note, you will also get errors as the configuration files are not yet in place.

## Create the required folders and add configuration files
If any folders are missing, create them using the following commands:
```
mkdir criabot_data
mkdir criadex_data
mkdir criaparse_data
mkdir criaembed-api_data
mkdir criaembed-app_data
mkdir html
mkdir log
mkdir moodledata
mkdir elasticsearch_data
mkdir minio_data
mkdir ragflow_data
mkdir redis_data
mkdir user_data
mkdir user_scripts
```
Add the following configuration files to the respective folders:

0. **Criabot root**
   - .env (Used by docker-compose)
```
ELASTIC_PASSWORD=elastic
REDIS_PASSWORD=password
MYSQL_ROOT_PASSWORD=cria
MINIO_ROOT_USER=admin
MINIO_ROOT_PASSWORD=password
```

1. **criabot_data**
   - docker.env
```
# Criadex Settings
CRIADEX_API_BASE=http://criadex:25574
CRIADEX_API_KEY=password
CRIADEX_MASTER_API_KEY=password

# Redis Credentials (Cache)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_USERNAME=default
REDIS_PASSWORD=password

# MySQL Credentials (Management)
MYSQL_HOST=mysql
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=cria
MYSQL_DATABASE=criabot

# Initial API Key
APP_INITIAL_MASTER_KEY=password

# Criadex timeout
CRIADEX_SDK_IO_TIMEOUT=300

# Chat expiry time (e.g., 2h, 1d)
CHAT_EXPIRE_TIME=2h
```

2. **criadex_data**
   - docker.env
```
# Criadex API Settings
APP_API_MODE=PRODUCTION
APP_API_PORT=25574

# MySQL Credentials
MYSQL_HOST=mysql
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=cria
MYSQL_DATABASE=criadex

# Elasticsearch Credentials
ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200
ELASTICSEARCH_API_KEY=None
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=elastic

# Initial API Key
APP_INITIAL_MASTER_KEY=password
```

3. **criaembed-api_data**
   - docker.env
```
# MySQL Credentials
MYSQL_HOST=mysql
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=cria
MYSQL_DATABASE=criaembed

CRIA_SERVER_URL="http://localhost/"
CRIA_SERVER_TOKEN=<moodle_token>
CRIA_BOT_SERVER_URL="http://criabot/"
CRIA_BOT_SERVER_TOKEN=<bot_token>
THIS_APP_URL="http://localhost:3003/embed-api"
WEB_APP_URL="http://localhost:4000/embed"

DEFAULT_BOT_GREETING="Hello there! Got a question?"
APP_MODE=PRODUCTION

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=password

ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200
ELASTIC_PASSWORD=elastic
```

4. **criaparse_data**
   - docker.env
```
# Criaparse API Settings
APP_API_MODE=PRODUCTION
APP_API_PORT=25576

# Redis Credentials
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=password

# SDK For auth
CRIADEX_SDK_IO_TIMEOUT=500
CRIADEX_API_BASE=http://criadex:25574
CRIADEX_API_KEY=password
```

5. **ragflow_data**
   - docker.env
```
# Ragflow environment variables
# (Refer to Criabot/docker-compose.yml for passed environment)
RAGFLOW_API_KEY=<ragflow_api_key>
```
## Initial Database Setup and Migrations
After the containers are running, you must initialize the databases and apply the necessary migrations for the parent-child bot feature.

1. **Clear existing data (if any)**:
```bash
sudo docker exec cria-prod-mysql-1 mysql -uroot -pcria -e "DROP DATABASE IF EXISTS criadex; DROP DATABASE IF EXISTS criabot; DROP DATABASE IF EXISTS cria; DROP DATABASE IF EXISTS criaembed;"
```

2. **Import clean database**:
```bash
sudo docker exec -i cria-prod-mysql-1 mysql -uroot -pcria < path/to/cria-clean.sql
```

3. **Apply BotParents migration**:
```bash
sudo docker exec -i cria-prod-mysql-1 \
  mysql -uroot -pcria criabot \
  < ./Criabot/migrations/001_add_bot_parents_table.sql
```

## Configure Ragflow
Once the database is ready, you need to configure the Ragflow tenant and API key.

1. **Access Ragflow UI**:
   Open your browser and navigate to: `http://localhost:9381/`

2. **Create/Login to Ragflow**:
   Sign up for a new account. This will create a new tenant in the Ragflow database.

3. **Configure LLM Models**:
   In the Ragflow UI, navigate to Settings and configure your preferred LLM (e.g., Azure OpenAI, OpenAI), Embedding, and Rerank models.

4. **Generate API Key**:
   Navigate to the API Keys section in Ragflow and create a new key. Copy this key (format: `ragflow-XXXX...`).

5. **Get Tenant ID**:
   Query the database to find your tenant ID:
```bash
sudo docker exec cria-prod-mysql-1 mysql -uroot -pcria rag_flow -e "SELECT id, name FROM tenant ORDER BY create_time DESC LIMIT 5;"
```

6. **Update Environment Files**:
   Update your `.env` and `docker.env` files with the new `RAGFLOW_API_KEY` and `RAGFLOW_TENANT_ID`.

7. **Restart Services**:
```bash
docker-compose restart criabot criadex
```

## Change folder and file permissions
```
sudo chgrp -R docker criabot_data/ criadex_data/ criaembed-api_data/ criaembed-app_data/ criaparse_data/
```
```
sudo chmod -R 664 criabot_data/ criadex_data/ criaembed-api_data/ criaembed-app_data/ criaparse_data/
```
Folders user_data and user_scripts should be owned by the user and not the docker group.
```
sudo chown -R 1001:1001 user_data/ user_scripts/
sudo chmod -R 755 user_data/ user_scripts/
```
## Restart the containers
```
docker-compose down
docker-compose up -d
```
## Cria front end installation
Cria uses Moodle 4.1 as a framework. The following steps will get Cria working.
### Download Moodle
```
git clone git://git.moodle.org/moodle.git
cd moodle
git branch --track MOODLE_401_STABLE origin/MOODLE_401_STABLE
git checkout MOODLE_401_STABLE
```
### Remove the default html folder and replace it with the moodle folder
```
rm -rf html
mv moodle html
```
### Change permissions on folders
```
sudo chown -R www-data:eaas html/ moodledata/ log/
```
### Add the cria local and theme plugins
```
cd html/local
git clone git@github.com:YorkUITInnovation/moodle-local_cria.git cria
cd ../theme
git clone git@github.com:YorkUITInnovation/moodle-theme_cria.git cria

cd ../../
sudo chown -R www-data:eaas html/
sudo chmod -R 775 html/ 
(These will be changed later for better security)
```
### Restart the containers
```
docker-compose stop
docker-compose up -d
```
### Complete the installation
1. Go to http://your_cria_url:8080
2. Follow the installation instructions
3. Use the MySQL credentials provided.

Once installation is complete
1. <https://docs.docker.com/engine/install/ubuntu/#uninstall-docker-engine> [↑](#footnote-ref-2361)

2. <https://docs.docker.com/engine/install/ubuntu/#install-from-a-package> [↑](#footnote-ref-31270)