// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * player.js
 *
 * @package   mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
