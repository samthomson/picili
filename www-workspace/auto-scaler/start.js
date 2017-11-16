var cmd = require('node-cmd')
var os = require('os')

// const processOne = 'C:/wamp64/www/picili-auto/php artisan process'
const processOne = '/var/www/picili-auto/php artisan process'
var iBatchRuns = 1
const iMaxBatches = 8

var iCycleDelay = 60000

var bContinuous = true

runCycle()

function runCycle() {
    var iSuccess = 0
    var iFail = 0
    var aBatches = []
    for(var iBatchCount = 0; iBatchCount < iBatchRuns; iBatchCount++) {
        aBatches.push(processSingleBatch())
    }
    Promise.all(aBatches).then((aResults) => {
        aResults.forEach((bValue) => {
            if (bValue) {
                iSuccess++
            } else {
                iFail++
            }  
        })              
    }).catch((err) => {
        console.log(`\n\ncaught an error: ${err}\n\n`)
    }).then(() => {
        console.log(`finished all ${iBatchRuns} batches. ${iSuccess}/${iBatchRuns} succesful, ${iFail}/${iBatchRuns} failed`)
        // readjust batch size based on available resources
        var iCurrentPercentFree = iPercentFree()
        console.log('free memory: ', iCurrentPercentFree)
        if (iCurrentPercentFree > 20) {
            ChangeBatcheSize(1)
            console.log('increasing batches, now: ', iBatchRuns)
        }
        if (iCurrentPercentFree < 20) {
            ChangeBatcheSize(-1)
            console.log('decreasing batches, now: ', iBatchRuns)
        }

        // rerun
        if (bContinuous && iBatchRuns > 0) {
            // re-run immediately
            setTimeout(function () {
                // then reschdule
                runCycle()
            }, 0)
        } else {
            if(iBatchRuns === 0) {
                console.log('\n\nBATCH RUNS ZERO\n\n')
                console.log(`\n\nDelay ${iCycleDelay/1000} seconds\n\n`)
                // re-run after a delay (to let memory cool down)
                setTimeout(function () {
                    // then reschdule
                    console.log(`-- Restarting after ${iCycleDelay/1000} s delay\n\n`)
                    ChangeBatcheSize(1)
                    runCycle()
                }, iCycleDelay)
            }
        }
    })
    
}

function iPercentFree () {return os.freemem() / os.totalmem() * 10}
function ChangeBatcheSize(iDelta) {
    iBatchRuns += iDelta
    if (iBatchRuns < 2) iBatchRuns = 2
    if (iBatchRuns > iMaxBatches) iBatchRuns = iMaxBatches

    console.log(`batch size now ${iBatchRuns}`)
}

function processSingleBatch() {
    return new Promise(
        (resolve, reject) => {
            // don't start them all at exactly the same time, or they will prob crash
            var iWait = Math.floor(Math.random() * 5000)

            var exec = require('child_process').exec;
            var child = exec('cd ./../auto && php artisan process-all');
            child.stdout.on('data', function(data) {
                console.log('stdout: ' + data);
            });
            child.stderr.on('data', function(data) {
                if (data) {
                    console.log('stderr: ' + data);
                }
            });
            child.on('close', function(code) {
                resolve(parseInt(code) === 0)
            });
            // console.log('free memory (resolve,reject): ', iPercentFree())
        }
    )
}
function iPercentFree () {return os.freemem() / os.totalmem() * 100}