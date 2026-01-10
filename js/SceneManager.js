import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import gsap from 'gsap';

export default class SceneManager {
    constructor(container) {
        this.container = container;
        this.width = window.innerWidth;
        this.height = window.innerHeight;

        this.init();
        this.createObjects();
        this.animate();
        this.setupResize();

        // Trigger entrance
        this.introAnimation();
    }

    init() {
        // Scene
        this.scene = new THREE.Scene();
        // Fog for depth
        this.scene.fog = new THREE.FogExp2(0x050505, 0.002);

        // Camera
        this.camera = new THREE.PerspectiveCamera(75, this.width / this.height, 0.1, 1000);
        this.camera.position.z = 5;

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(this.width, this.height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setClearColor(0x000000, 0); // Transparent to let CSS gradient show
        this.container.appendChild(this.renderer.domElement);

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        const pointLight = new THREE.PointLight(0x00f3ff, 2, 50); // Cyan accent light
        pointLight.position.set(2, 3, 4);
        this.scene.add(pointLight);

        const pointLight2 = new THREE.PointLight(0xff00ff, 2, 50); // Magenta rim light
        pointLight2.position.set(-2, 3, -4);
        this.scene.add(pointLight2);
    }

    createObjects() {
        // --- High-Tech Laptop Group ---
        this.laptopGroup = new THREE.Group();

        // Materials
        const chassisMat = new THREE.MeshStandardMaterial({
            color: 0x0a0a0a,
            roughness: 0.2,
            metalness: 0.9,
        });
        const keyMat = new THREE.MeshStandardMaterial({
            color: 0x050505,
            roughness: 0.5,
            metalness: 0.5
        });
        const screenBorderMat = new THREE.MeshStandardMaterial({
            color: 0x080808,
            roughness: 0.2,
            metalness: 0.8
        });

        // 1. Base (Bottom Chassis)
        const baseGeo = new THREE.BoxGeometry(3, 0.1, 2);
        this.laptopBase = new THREE.Mesh(baseGeo, chassisMat);
        this.laptopGroup.add(this.laptopBase);

        // 2. Keyboard Area (凹 indentation)
        // Creating keys procedurally for "Tech" look
        const keyGroup = new THREE.Group();
        const keyGeo = new THREE.BoxGeometry(0.18, 0.05, 0.18);

        // Simple grid of keys
        for (let x = -1.2; x <= 1.2; x += 0.22) {
            for (let z = -0.5; z <= 0.5; z += 0.22) {
                const key = new THREE.Mesh(keyGeo, keyMat);
                key.position.set(x, 0.06, z); // Slightly above base
                keyGroup.add(key);
            }
        }
        this.laptopBase.add(keyGroup);

        // 3. Screen Hinge/Lid
        this.screenGroup = new THREE.Group();
        this.screenGroup.position.set(0, 0.05, -1); // Hinge at back of base

        // Screen Chassis
        const screenChassisGeo = new THREE.BoxGeometry(3, 2, 0.1);
        const screenChassis = new THREE.Mesh(screenChassisGeo, screenBorderMat);
        screenChassis.position.set(0, 1, 0); // Center relative to hinge
        this.screenGroup.add(screenChassis);

        // Illuminating Display
        const displayGeo = new THREE.PlaneGeometry(2.8, 1.8);
        // Create a dynamic tech texture or gradient
        const displayMat = new THREE.MeshBasicMaterial({
            color: 0x00f3ff,
            side: THREE.DoubleSide
        });
        this.laptopDisplay = new THREE.Mesh(displayGeo, displayMat);
        this.laptopDisplay.position.set(0, 1, 0.06); // Slightly in front of chassis
        this.screenGroup.add(this.laptopDisplay);

        // Initial open state
        this.screenGroup.rotation.x = 0; // Closed initially, will open in animation

        this.laptopGroup.add(this.screenGroup);
        this.scene.add(this.laptopGroup);

        // --- Additional 3D Elements ---
        this.createBackgroundLaptops();
        this.createRobotics();

        // --- Environment ---

        // Tech Grid (Cyberpunk floor)
        const gridHelper = new THREE.GridHelper(60, 60, 0x222222, 0x050505);
        gridHelper.position.y = -2;
        this.scene.add(gridHelper);

        // Floating Particles (Data streams)
        const particlesGeometry = new THREE.BufferGeometry();
        const particlesCount = 800;
        const posArray = new Float32Array(particlesCount * 3);

        for (let i = 0; i < particlesCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 30;
        }
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.03,
            color: 0x00f3ff,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending
        });
        this.particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        this.scene.add(this.particlesMesh);
    }

    createBackgroundLaptops() {
        this.backgroundLaptops = [];
        const geometry = new THREE.BoxGeometry(1.5, 0.05, 1);
        const material = new THREE.MeshStandardMaterial({ color: 0x222222, wireframe: true });

        for (let i = 0; i < 5; i++) {
            const mesh = new THREE.Mesh(geometry, material);

            // Random positions in background - MOVED CLOSER
            mesh.position.x = (Math.random() - 0.5) * 15;
            mesh.position.y = (Math.random() - 0.5) * 8;
            mesh.position.z = -2 - Math.random() * 5; // Closer z-depth

            mesh.rotation.x = Math.random() * Math.PI;
            mesh.rotation.y = Math.random() * Math.PI;

            this.scene.add(mesh);
            this.backgroundLaptops.push(mesh);
        }
    }

    createRobotics() {
        // Create a "Drone Scout" - Sphere with orbiting rings
        this.droneGroup = new THREE.Group();

        // Core
        const coreGeo = new THREE.IcosahedronGeometry(0.6, 1); // Slightly larger
        const coreMat = new THREE.MeshStandardMaterial({
            color: 0xff00ff,
            emissive: 0xff00ff, // Brighter emissive
            emissiveIntensity: 2,
            roughness: 0.2,
            metalness: 1.0,
            flatShading: true
        });
        this.droneCore = new THREE.Mesh(coreGeo, coreMat);
        this.droneGroup.add(this.droneCore);

        // Ring 1
        const ringGeo = new THREE.TorusGeometry(1.0, 0.05, 8, 50);
        const ringMat = new THREE.MeshBasicMaterial({ color: 0x00f3ff, wireframe: true, transparent: true, opacity: 0.8 });
        this.droneRing1 = new THREE.Mesh(ringGeo, ringMat);
        this.droneGroup.add(this.droneRing1);

        // Ring 2
        const ringGeo2 = new THREE.TorusGeometry(1.4, 0.02, 8, 50);
        this.droneRing2 = new THREE.Mesh(ringGeo2, ringMat);
        this.droneGroup.add(this.droneRing2);

        // Position it floating prominent
        this.droneGroup.position.set(3.5, 1.5, -1); // Closer
        this.scene.add(this.droneGroup);
    }

    animate = () => {
        requestAnimationFrame(this.animate);

        // Rotations
        const time = Date.now() * 0.001;

        if (this.laptopGroup) {
            // Auto rotation (idle)
            const time = Date.now() * 0.001;

            // Mouse Parallax Logic
            // Smoothly interpolate current rotation to target rotation based on mouse
            this.targetRotationX = this.mouseY * 0.5; // Vertical tilt limits
            this.targetRotationY = this.mouseX * 0.5; // Horizontal rotate limits

            // Apply slight smoothing
            this.laptopGroup.rotation.x += (this.targetRotationX - this.laptopGroup.rotation.x) * 0.05;
            this.laptopGroup.rotation.y += (this.targetRotationY - this.laptopGroup.rotation.y) * 0.05;

            // Scroll Logic
            // Move laptop up/back when scrolling down
            const scrollFactor = this.scrollY * 0.002;
            this.laptopGroup.position.z = -scrollFactor * 2; // Move away
            this.laptopGroup.rotation.x += scrollFactor * 0.5; // Tilt up

            // Add floating idle motion on top
            this.laptopGroup.position.y = Math.sin(time) * 0.1 + (scrollFactor * 0.5); // Move up slightly on scroll
        }

        // Animate Background Laptops
        if (this.backgroundLaptops) {
            this.backgroundLaptops.forEach((laptop, i) => {
                laptop.rotation.x += 0.01;
                laptop.rotation.y += 0.005;
                laptop.position.y += Math.sin(Date.now() * 0.001 + i) * 0.005; // Bobbing
            });
        }

        // Animate Robotics (Drone)
        if (this.droneGroup) {
            this.droneGroup.rotation.y = Date.now() * 0.0005;
            this.droneGroup.position.y = 2 + Math.sin(Date.now() * 0.002) * 0.5; // Float

            this.droneRing1.rotation.x = Date.now() * 0.002;
            this.droneRing1.rotation.y = Date.now() * 0.001;

            this.droneRing2.rotation.z = -Date.now() * 0.0015;
        }

        if (this.particlesMesh) {
            this.particlesMesh.rotation.y = -Date.now() * 0.0001;
            // Particles also react slightly to mouse
            this.particlesMesh.rotation.x += (this.mouseY * 0.2 - this.particlesMesh.rotation.x) * 0.02;
            this.particlesMesh.position.y = this.scrollY * 0.001; // Particles move up
        }

        this.renderer.render(this.scene, this.camera);
    }

    setupScroll() {
        this.scrollY = 0;
        window.addEventListener('scroll', () => {
            this.scrollY = window.scrollY;
        });
    }

    introAnimation() {
        if (!this.laptopGroup) return;

        // Initial positions (Hidden/Closed)
        this.laptopGroup.position.z = -5; // Far away
        this.laptopGroup.rotation.x = 0.5; // Tilted
        this.screenGroup.rotation.x = Math.PI / 2; // Flat closed (approx 90 deg)

        // 1. Fly in
        gsap.to(this.laptopGroup.position, {
            z: 0,
            duration: 2,
            ease: "power3.out"
        });

        // 2. Open screen
        gsap.to(this.screenGroup.rotation, {
            x: -0.5, // Open angle
            duration: 2.5,
            delay: 0.5,
            ease: "power2.inOut"
        });

        // 3. Float normalize
        gsap.to(this.laptopGroup.rotation, {
            x: 0,
            duration: 2,
            ease: "power2.out"
        });
    }

    setupResize() {
        window.addEventListener('resize', this.onWindowResize.bind(this));

        // Mouse interaction
        this.mouseX = 0;
        this.mouseY = 0;
        this.targetRotationX = 0;
        this.targetRotationY = 0;

        window.addEventListener('mousemove', (event) => {
            this.mouseX = (event.clientX / window.innerWidth) * 2 - 1;
            this.mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
        });
    }

    onWindowResize() {
        this.width = window.innerWidth;
        this.height = window.innerHeight;

        this.camera.aspect = this.width / this.height;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(this.width, this.height);
    }
}
