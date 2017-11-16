var os = require('os');

console.log(os.cpus());
console.log(os.totalmem()/1024/1024);
console.log(os.freemem()/1024/1024)

iPercentFree = os.freemem() / os.totalmem() * 100

console.log('free mem: ', iPercentFree)

function iPercentFree () {return os.freemem() / os.totalmem() * 10}