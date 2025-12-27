import SceneManager from './SceneManager.js';

document.addEventListener('DOMContentLoaded', () => {
    const canvasContainer = document.getElementById('canvas-container');
    const sceneManager = new SceneManager(canvasContainer);
});
