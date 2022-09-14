counterparty-json-cache
---
counterparty-json-cache is a php script which populates a mysql database with counterparty assets JSON data.

Command line arguments 
---
```
--testnet    Load data from testnet
--asset=X    Update JSON for a given asset X
--block=#    Lookup asset issuance updates since a given block
--all        Full parse of all JSON files 
```

Database Information
---
- [counterparty_json](sql/counterparty_json.sql)


Helpful? Donate BTC, XCP or any Counterparty asset to 1JDogZS6tQcSxwfxhv6XKKjcyicYA4Feev