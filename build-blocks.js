const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

/**
 * Auto-generate individual block builds for DigiCommerce
 * Handles JavaScript, CSS compilation and block.json copying
 */
function generateBlockBuilds() {
    // Paths (script is running from plugin root)
    const blocksDir = path.join(__dirname, 'blocks');
    const outputDir = path.join(__dirname, 'assets', 'blocks');
    
    console.log('🔍 Scanning blocks directory:', blocksDir);
    console.log('📦 Output directory:', outputDir);
    
    // Read all directories in the blocks folder
    const blockDirs = fs.readdirSync(blocksDir, { withFileTypes: true })
        .filter(dirent => dirent.isDirectory())
        .map(dirent => dirent.name)
        .filter(name => name !== 'node_modules' && !name.startsWith('.') && name !== 'dist');
    
    console.log('📁 Found directories:', blockDirs);
    
    // Check which directories have files to build
    const validBlocks = blockDirs.filter(blockName => {
        const indexPath = path.join(blocksDir, blockName, 'index.js');
        const scriptPath = path.join(blocksDir, blockName, 'script.js');
        const stylePath = path.join(blocksDir, blockName, 'style.css');
        const blockJsonPath = path.join(blocksDir, blockName, 'block.json');
        const hasJs = fs.existsSync(indexPath);
        const hasScript = fs.existsSync(scriptPath);
        const hasCss = fs.existsSync(stylePath);
        const hasBlockJson = fs.existsSync(blockJsonPath);
        
        console.log(`  ${blockName}: JS ${hasJs ? '✅' : '❌'} | Script ${hasScript ? '✅' : '❌'} | CSS ${hasCss ? '✅' : '❌'} | JSON ${hasBlockJson ? '✅' : '❌'}`);
        return hasJs || hasScript || hasCss || hasBlockJson;
    });
    
    console.log('📋 Valid blocks:', validBlocks);
    
    if (validBlocks.length === 0) {
        console.warn('⚠️  No valid blocks found');
        return [];
    }
    
    // Ensure output directory exists
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }
    
    // Create individual block directories and build info
    const buildInfo = validBlocks.map(blockName => {
        const blockOutputDir = path.join(outputDir, blockName);
        
        // Ensure the block output directory exists
        if (!fs.existsSync(blockOutputDir)) {
            fs.mkdirSync(blockOutputDir, { recursive: true });
        }
        
        const jsInput = path.join('blocks', blockName, 'index.js');
        const scriptInput = path.join('blocks', blockName, 'script.js');
        const cssInput = path.join('blocks', blockName, 'style.css');
        const blockJsonInput = path.join('blocks', blockName, 'block.json');
        const jsOutput = path.join('assets', 'blocks', blockName, 'index.js');
        const scriptOutput = path.join('assets', 'blocks', blockName, 'script.js');
        const cssOutput = path.join('assets', 'blocks', blockName, 'style.css');
        const blockJsonOutput = path.join('assets', 'blocks', blockName, 'block.json');
        
        return {
            name: blockName,
            js: {
                input: jsInput,
                output: jsOutput,
                exists: fs.existsSync(jsInput)
            },
            script: {
                input: scriptInput,
                output: scriptOutput,
                exists: fs.existsSync(scriptInput)
            },
            css: {
                input: cssInput,
                output: cssOutput,
                exists: fs.existsSync(cssInput)
            },
            blockJson: {
                input: blockJsonInput,
                output: blockJsonOutput,
                exists: fs.existsSync(blockJsonInput)
            }
        };
    });
    
    console.log(`\n✅ Prepared ${buildInfo.length} block builds`);
    return buildInfo;
}

/**
 * Copy block.json files
 */
function copyBlockJson(blocks) {
    const jsonBlocks = blocks.filter(block => block.blockJson.exists);
    
    if (jsonBlocks.length === 0) {
        console.log('⚠️  No block.json files to copy');
        return Promise.resolve();
    }
    
    console.log(`\n📄 Copying ${jsonBlocks.length} block.json files...`);
    
    jsonBlocks.forEach(block => {
        try {
            fs.copyFileSync(block.blockJson.input, block.blockJson.output);
            console.log(`✅ Copied block.json for ${block.name}`);
        } catch (error) {
            console.error(`❌ Failed to copy block.json for ${block.name}:`, error.message);
        }
    });
    
    return Promise.resolve();
}

/**
 * Build JavaScript for blocks using esbuild (index.js files)
 */
function buildJavaScript(blocks, isProduction = false) {
    const jsBlocks = blocks.filter(block => block.js.exists);
    
    if (jsBlocks.length === 0) {
        console.log('⚠️  No JavaScript blocks to build');
        return Promise.resolve();
    }
    
    console.log(`\n🏗️  Building ${jsBlocks.length} JavaScript blocks...`);
    
    const buildPromises = jsBlocks.map(block => {
        const esbuildArgs = [
            block.js.input,
            '--bundle',
            '--loader:.js=jsx',
            '--define:wp=window.wp',
            '--jsx-factory=wp.element.createElement',
            '--jsx-fragment=wp.element.Fragment',
            `--outfile=${block.js.output}`
        ];
        
        if (isProduction) {
            esbuildArgs.push('--minify');
        }
        
        try {
            execSync(`npx esbuild ${esbuildArgs.join(' ')}`, { stdio: 'inherit' });
            console.log(`✅ Built JS for ${block.name}`);
            return Promise.resolve();
        } catch (error) {
            console.error(`❌ Failed to build JS for ${block.name}:`, error.message);
            return Promise.reject(error);
        }
    });
    
    return Promise.all(buildPromises);
}

/**
 * Build frontend scripts (script.js files)
 */
function buildFrontendScripts(blocks, isProduction = false) {
    const scriptBlocks = blocks.filter(block => block.script.exists);
    
    if (scriptBlocks.length === 0) {
        console.log('⚠️  No frontend scripts to build');
        return Promise.resolve();
    }
    
    console.log(`\n📜 Building ${scriptBlocks.length} frontend scripts...`);
    
    const buildPromises = scriptBlocks.map(block => {
        const esbuildArgs = [
            block.script.input,
            '--bundle',
            `--outfile=${block.script.output}`
        ];
        
        if (isProduction) {
            esbuildArgs.push('--minify');
        }
        
        try {
            execSync(`npx esbuild ${esbuildArgs.join(' ')}`, { stdio: 'inherit' });
            console.log(`✅ Built frontend script for ${block.name}`);
            return Promise.resolve();
        } catch (error) {
            console.error(`❌ Failed to build frontend script for ${block.name}:`, error.message);
            return Promise.reject(error);
        }
    });
    
    return Promise.all(buildPromises);
}

/**
 * Build CSS for blocks using PostCSS
 */
function buildCSS(blocks, isProduction = false) {
    const cssBlocks = blocks.filter(block => block.css.exists);
    
    if (cssBlocks.length === 0) {
        console.log('⚠️  No CSS blocks to build');
        return Promise.resolve();
    }
    
    console.log(`\n🎨 Building ${cssBlocks.length} CSS blocks...`);
    
    const buildPromises = cssBlocks.map(block => {
        const postcssArgs = [
            block.css.input,
            '-o', block.css.output
        ];
        
        if (isProduction) {
            postcssArgs.push('--env', 'production');
        }
        
        try {
            execSync(`npx postcss ${postcssArgs.join(' ')}`, { stdio: 'inherit' });
            console.log(`✅ Built CSS for ${block.name}`);
            return Promise.resolve();
        } catch (error) {
            console.error(`❌ Failed to build CSS for ${block.name}:`, error.message);
            return Promise.reject(error);
        }
    });
    
    return Promise.all(buildPromises);
}

/**
 * Build all blocks (JS, CSS, script.js and copy block.json)
 */
function buildBlocks(isProduction = false) {
    const blocks = generateBlockBuilds();
    
    if (blocks.length === 0) {
        console.log('⚠️  No blocks to build');
        return Promise.resolve();
    }
    
    // Build JS, frontend scripts, CSS, and copy block.json
    return Promise.all([
        buildJavaScript(blocks, isProduction),
        buildFrontendScripts(blocks, isProduction),
        buildCSS(blocks, isProduction),
        copyBlockJson(blocks)
    ]);
}

/**
 * Watch mode for development
 */
function watchBlocks() {
    const blocks = generateBlockBuilds();
    
    if (blocks.length === 0) {
        console.log('⚠️  No blocks to watch');
        return;
    }
    
    console.log(`\n👀 Watching ${blocks.length} blocks for changes...`);
    
    // Start watch process for each block's JS (index.js)
    blocks.filter(block => block.js.exists).forEach(block => {
        const esbuildArgs = [
            block.js.input,
            '--bundle',
            '--loader:.js=jsx',
            '--define:wp=window.wp',
            '--jsx-factory=wp.element.createElement',
            '--jsx-fragment=wp.element.Fragment',
            `--outfile=${block.js.output}`,
            '--watch'
        ];
        
        try {
            const { spawn } = require('child_process');
            const child = spawn('npx', ['esbuild', ...esbuildArgs], {
                stdio: 'inherit',
                shell: true
            });
            
            console.log(`👀 Watching JS for ${block.name}...`);
            
            child.on('error', (error) => {
                console.error(`❌ JS watch failed for ${block.name}:`, error.message);
            });
            
        } catch (error) {
            console.error(`❌ Failed to start JS watch for ${block.name}:`, error.message);
        }
    });
    
    // Start watch process for each block's frontend script (script.js)
    blocks.filter(block => block.script.exists).forEach(block => {
        const esbuildArgs = [
            block.script.input,
            '--bundle',
            `--outfile=${block.script.output}`,
            '--watch'
        ];
        
        try {
            const { spawn } = require('child_process');
            const child = spawn('npx', ['esbuild', ...esbuildArgs], {
                stdio: 'inherit',
                shell: true
            });
            
            console.log(`👀 Watching frontend script for ${block.name}...`);
            
            child.on('error', (error) => {
                console.error(`❌ Frontend script watch failed for ${block.name}:`, error.message);
            });
            
        } catch (error) {
            console.error(`❌ Failed to start frontend script watch for ${block.name}:`, error.message);
        }
    });
    
    // Start watch process for each block's CSS
    blocks.filter(block => block.css.exists).forEach(block => {
        const postcssArgs = [
            block.css.input,
            '-o', block.css.output,
            '--watch'
        ];
        
        try {
            const { spawn } = require('child_process');
            const child = spawn('npx', ['postcss', ...postcssArgs], {
                stdio: 'inherit',
                shell: true
            });
            
            console.log(`👀 Watching CSS for ${block.name}...`);
            
            child.on('error', (error) => {
                console.error(`❌ CSS watch failed for ${block.name}:`, error.message);
            });
            
        } catch (error) {
            console.error(`❌ Failed to start CSS watch for ${block.name}:`, error.message);
        }
    });

    // Watch for block.json changes
    const chokidar = require('chokidar');
    blocks.filter(block => block.blockJson.exists).forEach(block => {
        const watcher = chokidar.watch(block.blockJson.input);

        watcher.on('change', () => {
            try {
                fs.copyFileSync(block.blockJson.input, block.blockJson.output);
                console.log(`🔄 Updated block.json for ${block.name}`);
            } catch (error) {
                console.error(`❌ Failed to update block.json for ${block.name}:`, error.message);
            }
        });

        console.log(`👀 Watching block.json for ${block.name}...`);
    });
}

// Handle command line arguments
const args = process.argv.slice(2);
const command = args[0];

// Handle errors gracefully
process.on('uncaughtException', (error) => {
    console.error('❌ Script failed:', error.message);
    console.log('💡 Make sure you have the correct directory structure and PostCSS is configured');
    process.exit(1);
});

// Execute based on command
switch (command) {
    case 'build':
        buildBlocks(true)
            .then(() => console.log('\n🎉 All blocks built successfully!'))
            .catch(() => process.exit(1));
        break;
        
    case 'dev':
        buildBlocks(false)
            .then(() => {
                console.log('\n🎉 Initial build complete!');
                watchBlocks();
            })
            .catch(() => process.exit(1));
        break;
        
    case 'watch':
        watchBlocks();
        break;
        
    default:
        // Just generate the structure
        const blocks = generateBlockBuilds();
        if (blocks.length > 0) {
            console.log(`\n🚀 Ready to build! Run:`);
            console.log(`  Development: npm run dev:js-blocks`);
            console.log(`  Production: npm run build:js-blocks`);
        } else {
            console.log(`\n⚠️  No blocks to build. Create some blocks first!`);
        }
}