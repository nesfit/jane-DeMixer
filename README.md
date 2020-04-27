# Coin DeMixer for BestMixer.io
## Introduction
Coin DeMixer web application offers a heuristic for deanonymization of transactions coming into and out of former BestMixer.io service. DeMixer helps to track the spending history of cryptocurrency assets laundered via BestMixer.

Coin DeMixer consolidates mixing approaches reverse-engineered from services such as Bitcoin Blender, Bitcoin Laundry, BestMixer.io, PrivCoin, Blender.io, and MixTum. Heuristic tailored for BestMixer.io was produced based on the behavior of cryptocurrency tumblers mentioned above.  Although [law enforcement authorities seized BestMixer.io](https://www.europol.europa.eu/newsroom/news/multi-million-euro-cryptocurrency-laundering-service-bestmixerio-taken-down), the heuristic and web application can be customized to support compatible (i.e., using the same principle as BestMixer) tumbling service.

This application is one of the modules of the JANE platform, which offers various mission-specific tools intended for digital forensics of computer networks. JANE follows microservice architecture and offers few containerized modules such as:

* [sMaSheD](https://github.com/kvetak/sMaSheD/) - tracks IP addresses and ports of well-known mining services. It also records the availability of mining service on;
* [Cryptoalarm](https://github.com/nesfit/jane-cryptoalarm/) - sends email/REST notifications triggered by the appearance of cryptocurrency address in new transactions;
* [DeMixer](https://github.com/nesfit/jane-DeMixer/) - DeMixer applies proof-of-concept heuristic (working on BestMixer.io cluster), which can correlate incoming and outgoing transactions going via mixing services.
* [Cryptoclients](https://github.com/nesfit/jane-cryptoclients/) - Blockbook web-application offers generic blockchain explorer supporting major cryptocurrencies (e.g., BTC, ETH, LTS, DASH, ZCASH). 
* [Toreator](https://github.com/nesfit/toreator-ui) - stores metadata about Tor relays including IP addresses, capabilities and time when they were active
* [MozArch](https://github.com/nesfit/mozarch/) - MozArch is web-application that periodically downloads, parses, decodes, and archives (in the MAFF) webpages appearing on the public Internet.

JANE and its modules are outcomes of the [TARZAN project](https://www.fit.vut.cz/research/project/1063/.en) supported by the [Ministry of the Interior of the Czech Republic](https://www.mvcr.cz). Coin DeMixer was developed in the frame of the [master thesis of Matyáš Anton](https://www.vutbr.cz/en/students/final-thesis/detail/121966?zp_id=121966) supervised by [Vladimír Veselý](https://www.fit.vut.cz/person/veselyv/) in 2019.

 ## Goal
The primary motivation behind DeMixer's development was a necessity to address cryptocurrency tumbling as a predominant obfuscation technique when laundering assets connected with criminal activity.

DeMixer takes incoming/outgoing Bitcoin transactions processed by BestMixer.io as input and guesses corresponding outgoing/incoming transactions based on value, service and transaction fees, and time window.

## Technologies
DeMixer queries official cryptocurrency client (such as Bitcoin Core available as [another JANE module](https://github.com/nesfit/jane-cryptoclients/)) via RPC. DeMixer works with clustering data provided either by [WalletExplorer](https://www.walletexplorer.com/) web application or own clustering system (through defined API).

Coin DeMixer is a web application written in PHP with the help of the Laravel framework and Guzzle HTTP client:

* PHP 7.1.3
* Laravel 5.8
* Guzzle 6.3

## Installation guideline
Coin DeMixer web application source codes are available in the followin [GitHub repository folder](https://github.com/nesfit/jane-DeMixer/tree/master/demixer). Deployment of JANE DeMixer module can be cloned via [Git repositry](https://github.com/nesfit/jane-DeMixer.git).

### Prerequisites
All JANE modules run as containerized microservices. Therefore, the production environment is the same for all of them. JANE uses Docker for containerization. We expect that JANE containers can operate on any containerization solution compatible with Docker (such as Podman).

JANE was developed and tested on CentOS 7/8,  but it can be run on any operating system satisfying the following configuration. Here is a list of installation steps to successfully configure the hosting system:

1. enable routing for (virtual) network interface cards `/sbin/sysctl -w net.ipv4.ip_forward=1`

2. enable NAT on outside facing interfaces `firewall-cmd --zone=public --add-masquerade --permanent
install`

3. add Docker repository 
```
yum-config-manager \
    --add-repo \
    https://download.docker.com/linux/centos/docker-ce.repo
```

4. install Docker package and its prerequisites `yum install -y docker-ce`

5. run Docker as system service and enable it as one of the daemons 
```
systemctl start docker
systemctl enable docker
``` 

6. install docker-compose staging application 
```
sudo curl -L "https://github.com/docker/compose/releases/download/1.25.5/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

7. install Docker add-on which allows to specify destinations for dynamically created volumes
```
cp /usr/local/bin/docker-compose /sbin/docker-compose
curl -fsSL https://raw.githubusercontent.com/MatchbookLab/local-persist/master/scripts/install.sh | sudo bash
```

### Deployment
Coin DeMixer consists of two containers - Laravel 5.8 web application and nginx 1.10 HTTP server. In order to deploy DeMixer on your server:

1. clone DeMixer repository `git clone https://github.com/nesfit/jane-DeMixer.git`

2. copy container environmental variables file `cp .env.example .env`

3. specify in `.env` public port on which DeMixer will be available and existing virtual network name nano .env
```
NETWORK=<docker_network>
HTTP_PORT=<public_port>
```
4. copy web application environmental variables file 
`cp ./demixer/.env.example ./demixer/.env`

5. specify in `./demixer/.env` following parameters (where for Bitcoin/Litecoin clients you may consider to deploy JANE [cryptoclients module](https://github.com/nesfit/jane-cryptoclients/))
```
CLUSTER_CLIENT=<DeMixer compatible cluster provider>
BTC_CORE_HOSTNAME=<host running official Bitcoin client>
BTC_CORE_PORT=<port for RPC calls>
BTC_CORE_USERNAME=<Bitcoin client RPC username>
BTC_CORE_PASSWORD=<Bitcoin client RPC password>
LTC_CORE_HOSTNAME=<host running official Litecoin client>
LTC_CORE_PORT=<port for RPC calls>
LTC_CORE_USERNAME=<Litecoin client RPC username>
LTC_CORE_PASSWORD=<Litecoin client RPC password>
```
6. pull containers from [Docker hub repository](https://hub.docker.com/repository/docker/nesatfit/demix-app) `docker-compose pull`

7. optionally build web application locally `docker-compose build demix-app`

8. run containers `docker-compose up -d`

## User manual
### Heuristic
The heuristic works with the premise that mixing service operator (such as BestMixer.io) uses a single waller (i.e., cluster of addresses) to handle cryptocurrency assets. This means that for any incoming transaction (i.e., mixing service user depositing assets for laundering) to the cluster, there exist one or more subsequent outgoing transactions from that same cluster returning tumbled cryptocurrencies (back to the user). Outgoing transaction(s) should satisfy the following conditions:

* to have the same value as incoming transaction minus blockchain and operator's fee
* to happen later than the incoming transaction but no later than service mixing upper bound limit

Pseudo-algorithm behind heuristic goes through these steps when determining matching outgoing transactions for a given input:
1. Determine cluster **_C_** belonging to mixing service
2. Remember time **_t_** of incoming transaction and its output value **_v_**
3. Iterate through a list of all transactions **_L_** in blockchain from **_t_** to **_t + dmax_**, where **_dmax_** represents maximum delay for laundering, which mixing service offers
4. Select only those transactions in **_L_**, which satisfy: 
a) has one or more addresses from **_C_** as inputs; 
b) has one or more addresses not from **_C_** as outputs; 
c) output value for any address satisfying b) equals to **_v_** &ndash;
 **_fee_**, where **_fee_** is in the range of acceptable fees as announced by mixing service

### User stories
DeMixer is a web application that does not need any authorized user access. DeMixer page consists of the following views:
* Basic search
* Advanced search
* Search results

Basic search works with 

## Programmer's documentation
The programmer's documentation for DeMixer is autogenerated with the help of phpDox. This documentation is available statically in `docs` [folder](https://github.com/nesfit/jane-DeMixer/tree/master/demixer/docs). Moreover, for your convenience, it is also [available online](https://jane.nesad.fit.vutbr.cz/docs/demixer/index.xhtml) through JANE's [landing page]().

### Class Diagram
Class diagram corresponding to source codes of final application:

### Entity Relationship Diagram


