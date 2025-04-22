const fs = require('fs');
const postcss = require('postcss');
const chokidar = require('chokidar');
const path = require('path');

// Get absolute paths
const pluginsDir = path.resolve(process.cwd(), '..');
const frontCssFile = path.join(process.cwd(), 'assets/css/front.css');
const adminCssFile = path.join(process.cwd(), 'assets/css/admin/admin.css');
const outputFile = path.join(pluginsDir, 'digicommerce-pro', 'classes.txt');

// Pre-process CSS to fix malformed selectors
function fixCssSelectors(css) {
    // Simple approach to fix the missing closing parentheses or complete patterns
    // Add a closing parenthesis to any :where that's missing it
    return css.replace(/:where\(\[dir="(ltr|rtl)"\](?!\))/g, ':where([dir="$1"])');
}

async function extractClassesFromFile(filePath) {
    try {
        if (!fs.existsSync(filePath)) {
            return new Set();
        }

        // Read the CSS file and fix malformed selectors
        let css = fs.readFileSync(filePath, 'utf8');
        css = fixCssSelectors(css);
        
        // Process with PostCSS
        const result = await postcss().process(css, { from: filePath });

        // Extract class names
        const classNames = new Set();
        result.root.walkRules((rule) => {
            if (rule.selector && !rule.selector.startsWith('@')) {
                rule.selector.split(',').forEach((selector) => {
                    selector = selector.trim();
                    if (selector.startsWith('.')) {
                        // Get just the class name part (up to a space, [, :, etc.)
                        const match = selector.match(/\.([\w-\\:]+)/);
                        if (match && match[0]) {
                            classNames.add(match[0]);
                        }
                    }
                });
            }
        });

        return classNames;
    } catch (error) {
        // If PostCSS fails, try a direct regex approach as fallback
        try {
            const css = fs.readFileSync(filePath, 'utf8');
            const fixedCss = fixCssSelectors(css);
            const classRegex = /\.([\w-\\:]+)/g;
            const classNames = new Set();
            let match;
            
            while ((match = classRegex.exec(fixedCss)) !== null) {
                if (match[0].startsWith('.')) {
                    classNames.add(match[0]);
                }
            }
            
            return classNames;
        } catch (fallbackError) {
            return new Set();
        }
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
            process.exit(1);
        });
}