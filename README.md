# Mintos TASK
Create a quick API that will allow clients to exchange currency between their accounts.

P.S. I had not worked with Symphony before, so due to lack of time, it was decided to do the task using Laravel. API protection is also implemented using a regular api key (if there was more time, I would choose an implementation with user authorization and receiving a JWT token)
</br></br>P.P.S. I ask you to give feedback on the assignment in any case.

# Installation
1. [x] Download project with **git clone** help
2. [x] Copy .env.example file, as .env
3. [x] Power on DOCKER on your PC
4. [x] Install necessary pacs : **composer install** <br />(composer need to be installed in your PC)
5. [x] RUN **docker-compose up -d** , to power on containers
6. [x] RUN: **docker exec -it mintos_laravel-laravel.test-1 php artisan migrate:fresh --seed** <br />
   this will create and fill the necessary tables <br />
7. [x] have some fun with API - http:/localhost:80

# Working with API

For completeness, I created several users: </br>
    John Travolta :)</br>
    Emily Clarke  :)</br>
    Sidney Crosby :)</br>

-and linked several accounts to them. 

</br>You can transfer money between accounts, view accounts, view transaction history.</br></br>
If customer data is not enough for you, you can expand the database by writing an additional seed. Example - mintos/database/seeders (the task did not indicate the need to add clients and accounts via the API)

</br>API examples you can find in the Postman collection file: **mintos.postman_collection.json**
</br>Notice: When testing an API locally, you need to use the Postman Desktop Agent.</br> Safari doesn’t support the desktop agent.

**Description:**

/api/transfer - allow to transfer money from account to account (provided in mintos_clients_accounts table). You can transfer under 10.000

/api/client-accounts/{client_id} - show all accounts that belongs to selected client

/api/transaction-history/{account_id} - show all transaction history that belongs to selected account

/api/transaction-history/{account_id}?offset=1&limit=1 - show transaction history that belongs to selected account (with pagination)

P.S. all request must contains a api key, api key inside .env file: MINTOS_API_KEY

# Running tests
You can run tests in this way:  **docker exec -it mintos_laravel-laravel.test-1 php artisan test**

# Working with database
Database version: mysql Ver 8.0.32

You can work with database insider container mintos-mysql-1:
**docker exec -it mintos_laravel-mysql-1 mysql -uroot -p**;  password: password.
**USE laravel;**

SCHEME (FK on Cascade DELETE)
![img.png](img.png)

# Rates api
Used api for rates collecting: https://v6.exchangerate-api.com/v6/
