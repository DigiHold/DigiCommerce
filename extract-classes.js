const fs = require('fs');
const postcss = require('postcss');
const chokidar = require('chokidar');
const path = require('path');

// Get absolute paths
const pluginsDir = path.resolve(process.cwd(), '..');
const frontCssFile = path.join(process.cwd(), 'assets/css/front.css');
const adminCssFile = path.join(process.cwd(), 'assets/css/admin/admin.css');
const outputFile = path.join(pluginsDir, 'digicommerce-pro', 'classes.txt');

async function extractClassesFromFile(filePath) {
    try {
        if (!fs.existsSync(filePath)) {
            return new Set();
        }

        const css = fs.readFileSync(filePath, 'utf8');
        const result = await postcss().process(css, { from: filePath });

        // Extract class names
        const classNames = new Set();
        result.root.walkRules((rule) => {
            if (rule.selector && !rule.selector.startsWith('@')) {
                rule.selector.split(',').forEach((selector) => {
                    selector = selector.trim();
                    if (selector.startsWith('.')) {
                        classNames.add(selector);
                    }
                });
            }
        });

        return classNames;
    } catch (error) {
        return new Set();
    }
}

async function extractAllClasses() {
    try {
        // Extract classes from both files
        const frontClasses = await extractClassesFromFile(frontCssFile);
        const adminClasses = await extractClassesFromFile(adminCssFile);

        // Combine all classes
        const allClasses = new Set([...frontClasses, ...adminClasses]);

        // Sort classes
        const sortedClasses = Array.from(allClasses).sort();

        // Write to output file
        fs.writeFileSync(outputFile, sortedClasses.join('\n'), 'utf8');
    } catch (error) {}
}

// Check if running in watch mode
const watchMode = process.argv.includes('--watch');

if (watchMode) {
    // Watch mode for both files
    chokidar
        .watch([frontCssFile, adminCssFile], {
            persistent: true,
            ignoreInitial: false,
        })
        .on('all', (event, path) => {
            if (event === 'change' || event === 'add') {
                extractAllClasses();
            }
        });
} else {
    extractAllClasses()
        .then(() => {
            process.exit(0);
        })
        .catch((error) => {
            console.error('Error:', error);
            process.exit(1);
        });
}
