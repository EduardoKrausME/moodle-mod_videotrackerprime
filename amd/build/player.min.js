// This file is part of Moodle - http://moodle.org/.
/**
 * Creates the provider adapter declared by local_video_bridge.
 *
 * @module mod_videotrackerprime/player
 */
define([], function() {
    const create = (root, config) => new Promise((resolve, reject) => {
        if (!config || !config.adaptermodule) {
            reject(new Error('Missing Video Bridge adapter.'));
            return;
        }
        require([config.adaptermodule], (provider) => {
            if (!provider || typeof provider.create !== 'function') {
                reject(new Error('Invalid Video Bridge adapter.'));
                return;
            }
            Promise.resolve(provider.create(root, config)).then(resolve).catch(reject);
        }, reject);
    });
    return {create: create};
});
