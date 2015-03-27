# Poor Man Staticizer
A non-best-practice web cache generator.

## What it does
It simply reads a JSON configuration file and retrieves the content of a set of URIs. 
Then saves this content in simple html files. To help you use these files it generates a `rewrite_rules.txt` files with the rules you have to add in your apache virtual host.

## How it works
Take a look at  `static/example.com.json.sample` which is a sample config file for staticization task with Poor Man Staticizer

```json
{
  "pool": "generated/sample",
  "destination_pool": "static",
  "operation": "sample_staticization",
  "baseurl": "http://www.example.com",
  "pages": {
    "http://www.example.com/media": "media.html",
    "http://www.example.com/spring15/": "spring15.html",
  },
  "headers": [
    "Accept-Language:it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4,fr;q=0.2,ja;q=0.2,ko;q=0.2,zh-CN;q=0.2,zh;q=0.2,de;q=0.2,ru;q=0.2,vi;q=0.2",
    "Accept-Encoding:gzip, deflate, sdch",
    "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
  ]
}
```

- `pool` : tells to the application where to save the generated HTML
- `destination_pool` : tells the application where, on your server root, you want to place the html files, it's used to provide the correct rewrite rules
- `operation` : just a name for this staticization task
- `baseurl` : speak for itself
- `pages` :  a list of pages to be staticized along with the filename to save them
- `headers` : a list of request headers

## How can I use it
Just write your `config.json` file and then run the `run` script by typing
```shell
$ ./run --config='static/config.json'
```
Don't forget to chmod it if necessary
```shell
$ chmod +x run
```


