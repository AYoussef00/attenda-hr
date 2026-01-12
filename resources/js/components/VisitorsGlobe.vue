<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as THREE from 'three';

const props = defineProps<{
    countries: Array<{
        country_name: string;
        country_code: string;
        visits: number;
    }>;
}>();

const containerRef = ref<HTMLElement | null>(null);

let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let renderer: THREE.WebGLRenderer | null = null;
let animationId: number | null = null;
let globe: THREE.Mesh | null = null;

// Simple mapping from ISO country code to approximate lat/lng (center of country)
// Extend as needed.
const COUNTRY_COORDS: Record<string, { lat: number; lon: number }> = {
    SA: { lat: 24.0, lon: 45.0 },
    EG: { lat: 26.8, lon: 30.8 },
    AE: { lat: 24.2, lon: 54.4 },
    US: { lat: 39.8, lon: -98.6 },
    GB: { lat: 55.0, lon: -3.0 },
    FR: { lat: 46.0, lon: 2.0 },
    DE: { lat: 51.0, lon: 10.0 },
    IN: { lat: 22.0, lon: 79.0 },
    PK: { lat: 30.0, lon: 70.0 },
    PH: { lat: 13.0, lon: 122.0 },
    NG: { lat: 9.0, lon: 8.0 },
    KE: { lat: 0.0, lon: 37.9 },
    ZA: { lat: -30.0, lon: 25.0 },
};

const latLonToVector3 = (lat: number, lon: number, radius: number) => {
    const phi = (90 - lat) * (Math.PI / 180);
    const theta = (lon + 180) * (Math.PI / 180);

    const x = -radius * Math.sin(phi) * Math.cos(theta);
    const z = radius * Math.sin(phi) * Math.sin(theta);
    const y = radius * Math.cos(phi);

    return new THREE.Vector3(x, y, z);
};

const addMarkers = () => {
    if (!scene || !globe) return;

    // Remove previous markers
    const markers = scene.children.filter((c) => c.userData.type === 'marker');
    markers.forEach((m) => scene!.remove(m));

    const radius = (globe.geometry as THREE.SphereGeometry).parameters.radius + 0.02;

    props.countries.forEach((country) => {
        const code = (country.country_code || '').toUpperCase();
        const coord = COUNTRY_COORDS[code];
        if (!coord) return;

        const position = latLonToVector3(coord.lat, coord.lon, radius);

        const markerGeom = new THREE.SphereGeometry(0.02, 16, 16);
        const markerMat = new THREE.MeshBasicMaterial({ color: 0xffc857 });
        const marker = new THREE.Mesh(markerGeom, markerMat);
        marker.position.copy(position);
        marker.userData.type = 'marker';

        scene!.add(marker);
    });
};

const initScene = () => {
    const container = containerRef.value;
    if (!container) return;

    const width = container.clientWidth || 320;
    const height = container.clientHeight || 320;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(0, 0, 2.8);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(window.devicePixelRatio || 1);
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 3, 5);
    scene.add(directionalLight);

    // Globe (Earth) - inspired by three.js Earth example:
    // https://threejs.org/examples/webgpu_tsl_earth.html
    const radius = 1.0;
    const geometry = new THREE.SphereGeometry(radius, 64, 64);

    const textureLoader = new THREE.TextureLoader();
    const earthTexture = textureLoader.load(
        'https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg'
    );
    const earthNormal = textureLoader.load(
        'https://threejs.org/examples/textures/planets/earth_normal_2048.jpg'
    );

    const material = new THREE.MeshPhongMaterial({
        map: earthTexture,
        normalMap: earthNormal,
        shininess: 12,
        specular: new THREE.Color(0x222222),
    });

    globe = new THREE.Mesh(geometry, material);
    scene.add(globe);

    // Simple atmosphere glow
    const atmosphereGeometry = new THREE.SphereGeometry(radius * 1.02, 64, 64);
    const atmosphereMaterial = new THREE.MeshBasicMaterial({
        color: 0x4ade80,
        transparent: true,
        opacity: 0.15,
        side: THREE.BackSide,
    });
    const atmosphere = new THREE.Mesh(atmosphereGeometry, atmosphereMaterial);
    scene.add(atmosphere);

    addMarkers();

    const animate = () => {
        if (!scene || !camera || !renderer || !globe) return;

        animationId = requestAnimationFrame(animate);

        globe.rotation.y += 0.0025;

        renderer.render(scene, camera);
    };

    animate();

    const handleResize = () => {
        if (!renderer || !camera || !containerRef.value) return;
        const w = containerRef.value.clientWidth || 320;
        const h = containerRef.value.clientHeight || 320;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };

    window.addEventListener('resize', handleResize);

    // Cleanup resize listener on unmount
    onUnmounted(() => {
        window.removeEventListener('resize', handleResize);
    });
};

onMounted(() => {
    initScene();
});

watch(
    () => props.countries,
    () => {
        addMarkers();
    },
    { deep: true }
);

onUnmounted(() => {
    if (animationId !== null) {
        cancelAnimationFrame(animationId);
    }
    if (renderer) {
        renderer.dispose();
    }
    scene = null;
    camera = null;
    renderer = null;
    globe = null;
});
</script>

<template>
    <div ref="containerRef" class="mx-auto h-80 w-80">
        <!-- Three.js canvas will be injected here -->
    </div>
</template>


