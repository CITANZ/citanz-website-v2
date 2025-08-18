# CITA WEB 简易开(jie)发(pan)手册
友情提醒：如果下面的内容你看不懂，切莫头铁硬上。那么多现成云工具，总有几款适合你

## 后端
[SilverStripe 4](https://docs.silverstripe.org/en/4/)

### 咋弄？
```
composer install
```

## 前端
[VueJs](https://v2.vuejs.org/) v2

### 咋弄？
```
npm install;npm run prod
```
or
```
yarn;yarn prod
```

#### 前端要node几？
```
nvm use
```

## 然后呢？
根据你本地LAMP/XAMP的情况，设置.env文件


### 再然后呢？
```
sake dev/build flush=all
```

### 设置不好
到CITA群志愿者群里提问


#### 我不是志愿者
我也不是你老师


### 有现成的db和assets吗？
有。来到CITA群志愿者群里提问


#### 我都说我不是志愿者了
君子袒蛋蛋，小人藏鸡鸡 -- 那您的意思是？


## 前后端auth通信设置（signin以及`/member/`下面的功能都要用到）
往下看

------

## OAuth Setup
- Generate oauth encryption key and set `OAUTH_ENCRYPTION_KEY` in .env file
    - `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`
- Create new OAuth Client in the CMS backend (/admin > OAuth Clients)
    - Add a Secret and keep a note of it
- Add client ID and Client Secret to `.env.local`
    - Add `VUE_APP_OAUTH_CLIENT_ID="client id"` and `VUE_APP_OAUTH_CLIENT_SECRET="client secret"`

### OAuth certificates

There are pre-generated certificates that are used for the OAuth server the can be found in `app/certs` the are directories
for every environment (dev, test, live).

When setting up the project run the following commands to set the correct permissions on the certs.

    chown [user]:[webserver-user] app/certs/[env]/private.key
    chown [user]:[webserver-user] app/certs/[env]/public.key

    chmod 660 app/certs/[env]/private.key
    chmod 660 app/certs/[env]/public.key

If the permissions aren't set correctly you will get 500 errors from the auth api.

#### How to generate certificates

Run the following commands to generate certificates.

    openssl genrsa -out private.key 4096
    openssl rsa -in private.key -pubout -out public.key

## How to experience it locally

From the directory of your project:

    docker-compose build
    docker-compose up -d

Your site will be visible at http://localhost:8080


