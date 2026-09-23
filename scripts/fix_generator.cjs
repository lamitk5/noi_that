const fs = require('fs');

let file = fs.readFileSync('scripts/generate_300_scenarios.cjs', 'utf8');
file = file.split("page.click('button[type=\"submit\"]').first()").join("page.locator('button[type=\"submit\"]').first().click()");
file = file.split("page.click('button[type=\"submit\"]')").join("page.locator('button[type=\"submit\"]').first().click()");
fs.writeFileSync('scripts/generate_300_scenarios.cjs', file);
console.log('Fixed script successfully');
