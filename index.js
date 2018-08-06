#!/usr/bin/env node

let program = require('commander');
let ProvideCommand = require('./provider/command/provide-command');

program.version('0.0.1');
program
  .command(ProvideCommand.command())
  .option('-u --username <username>', 'Username to be used with basic HTTP auth')
  .option('-p --password <password>', 'Password to be used with basic HTTP auth')
  .action(ProvideCommand.action);

program.parse(process.argv);
