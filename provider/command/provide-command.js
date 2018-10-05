let puppeteer = require('puppeteer');
let fs = require('fs');
let tmp = require('tmp');
let _ = require('lodash');
let CdpEventObserverFactory = require('../cdp/event-observer');

module.exports = class {
  static command() {
    return 'provide <url> [otherUrls...]';
  }

  static action(url, otherUrls, options) {
    let trace = tmp.fileSync();
    let screenshot = tmp.fileSync();
    let urls = _.union([url], otherUrls || []);
    let output = {};

    (async () => {
      const browser = await puppeteer.launch();

      for (let key in urls) {
        await (async url => {
          let crawledUrlOutput = {};

          const page = await browser.newPage();
          if (options.username && options.password) {
            page.authenticate({ username: options.username, password: options.password });
          }

          const cdp = await page.target().createCDPSession();

          const cdpEventObserver = CdpEventObserverFactory.create(cdp);
          await cdpEventObserver.start();

          const viewport = Object.assign(page.viewport(), {width: 1366});
          await page.setViewport(viewport);
          await page.tracing.start({ path: trace.name, screenshots: true });

          await page.goto(url, { waitUntil : "networkidle2", timeout: 30000 });

          if (options.screenshot) {
            await page.screenshot({
              path: screenshot.name,
              fullPage: true,
              type: "jpeg",
              quality: 50,
              encoding: "base64"
            });
          }

          await cdpEventObserver.stop();

          crawledUrlOutput.events = cdpEventObserver.getEvents();

          await page.tracing.stop();

          crawledUrlOutput.trace = JSON.parse(fs.readFileSync(trace.name, 'utf-8'));
          crawledUrlOutput.screenshot = options.screenshot ? fs.readFileSync(screenshot.name, 'utf-8') : null;
          crawledUrlOutput.html = await page.content();

          trace.removeCallback();

          output[url] = crawledUrlOutput;
        })(urls[key]);
      }

      await browser.close();

      process.stdout.write(JSON.stringify(output));
    })().catch(error => {
      process.stderr.write(error.message);
      process.exit(1);
    });
  }
};
