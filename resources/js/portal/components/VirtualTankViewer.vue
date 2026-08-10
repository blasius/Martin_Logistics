<template>
    <div class="relative w-full rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shadow-2xl">
        <!-- WebGL Canvas Container -->
        <div ref="mount" class="w-full h-[480px] md:h-[540px]"></div>

        <!-- Error Fallback -->
        <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-slate-900">
            <p class="text-xs font-black uppercase text-slate-500 tracking-wider">3D Real-time Preview Unavailable</p>
        </div>

        <!-- Digital Overlay Readout -->
        <div v-else class="absolute top-4 left-4 rounded-xl bg-slate-900/80 backdrop-blur-md border border-white/10 p-4 text-white pointer-events-none shadow-lg">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tank Telemetry</p>
            <div class="flex items-baseline gap-2 mt-0.5">
                <span class="text-2xl font-black leading-none" :class="isLow ? 'text-rose-400' : 'text-amber-400'">{{ levelPercent }}%</span>
                <span class="text-xs font-bold text-slate-300">Capacity Fill</span>
            </div>
            <p class="text-xs font-semibold text-slate-300 mt-1">
                {{ currentLevelFormatted }} / {{ capacityFormatted }} Liters
            </p>
        </div>

        <div class="absolute bottom-3 right-3 rounded-lg bg-slate-900/60 backdrop-blur px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400 border border-white/5 pointer-events-none">
            Orbit / Drag to Inspect
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const props = defineProps({
    capacity: { type: Number, default: 39000 },
    currentLevel: { type: Number, default: 18500 },
    reorderThreshold: { type: [Number, null], default: null },
    fuelType: { type: String, default: 'diesel' },
});

const mount = ref(null);
const error = ref(false);

const level = computed(() => {
    if (!props.capacity) return 0;
    return Math.max(0, Math.min(props.currentLevel / props.capacity, 1));
});
const levelPercent = computed(() => (level.value * 100).toFixed(1));
const isLow = computed(() => props.reorderThreshold != null && props.currentLevel <= props.reorderThreshold);
const capacityFormatted = computed(() => Math.round(props.capacity || 0).toLocaleString());
const currentLevelFormatted = computed(() => Math.round(props.currentLevel || 0).toLocaleString());

let renderer, scene, camera, controls;
let clipPlane, fluidBodyMaterial, waveSurfaceMesh, waveMaterial;
let animationFrameId;
const clock = new THREE.Clock();

// Tank Dimensions
const TANK_RADIUS = 1.4;
const TANK_LENGTH = 5.2;
const INNER_R = TANK_RADIUS - 0.04;
const INNER_L = TANK_LENGTH - 0.08;
const Y_CENTER = TANK_RADIUS + 0.4; // Center axis in World Y

// --- GLSL SHADERS FOR WAVE SURFACE TOP CAP ---
const waveVertexShader = `
  uniform float uTime;
  uniform float uWaveFrequency;
  uniform float uWaveAmplitude;
  varying vec3 vNormal;
  varying vec3 vLocalPosition;

  void main() {
    vNormal = normal;
    vLocalPosition = position;
    vec3 pos = position;

    // Continuous surface wave ripples
    float waveX = sin(pos.x * uWaveFrequency + uTime * 3.0);
    float waveY = cos(pos.y * uWaveFrequency + uTime * 2.5);
    pos.z += (waveX + waveY) * uWaveAmplitude; // Local Z is World Y after rotation

    vec4 worldPos = modelMatrix * vec4(pos, 1.0);
    gl_Position = projectionMatrix * viewMatrix * worldPos;
  }
`;

const waveFragmentShader = `
  uniform vec3 uColor;
  varying vec3 vNormal;

  void main() {
    vec3 lightDir = normalize(vec3(5.0, 10.0, 8.0));
    float diff = max(dot(vNormal, lightDir), 0.45);
    vec3 highlightColor = uColor * diff + vec3(0.06, 0.06, 0.01);
    gl_FragColor = vec4(highlightColor, 1.0);
  }
`;

function buildScene() {
    const yellowCanopyMat = new THREE.MeshStandardMaterial({
        color: 0xeab308,
        roughness: 0.25,
        metalness: 0.1
    });

    const stainlessSteelMat = new THREE.MeshPhysicalMaterial({
        color: 0xd1d5db,
        metalness: 0.95,
        roughness: 0.15,
        clearcoat: 0.8,
        clearcoatRoughness: 0.1
    });

    // Glass Material with fallback transparency and physical transmission
    const glassShellMat = new THREE.MeshPhysicalMaterial({
        color: 0xffffff,
        metalness: 0.0,
        roughness: 0.05,
        transmission: 0.9,
        transparent: true,
        opacity: 0.35,
        ior: 1.4,
        reflectivity: 0.9,
        clearcoat: 1.0,
        clearcoatRoughness: 0.1,
        side: THREE.DoubleSide
    });

    // --- 1. Station Canopy & Platform ---
    const platform = new THREE.Mesh(new THREE.BoxGeometry(9.0, 0.3, 5.0), yellowCanopyMat);
    platform.position.y = -0.15;
    scene.add(platform);

    const roof = new THREE.Mesh(new THREE.BoxGeometry(9.5, 0.4, 5.5), yellowCanopyMat);
    roof.position.y = 4.2;
    scene.add(roof);

    const pillarGeo = new THREE.BoxGeometry(0.35, 4.2, 0.35);
    const pillar1 = new THREE.Mesh(pillarGeo, yellowCanopyMat);
    pillar1.position.set(-4.0, 2.1, -2.2);
    const pillar2 = new THREE.Mesh(pillarGeo, yellowCanopyMat);
    pillar2.position.set(4.0, 2.1, -2.2);
    scene.add(pillar1, pillar2);

    // Side Fuel Pumps
    const pumpGeo = new THREE.BoxGeometry(0.7, 1.8, 0.7);
    const pumpLeft = new THREE.Mesh(pumpGeo, yellowCanopyMat);
    pumpLeft.position.set(-3.5, 0.9, 1.2);
    const pumpRight = new THREE.Mesh(pumpGeo, yellowCanopyMat);
    pumpRight.position.set(3.5, 0.9, 1.2);
    scene.add(pumpLeft, pumpRight);

    // --- 2. Outer Cage & Glass Tank Shell ---
    const tankGroup = new THREE.Group();

    const saddleGeo = new THREE.BoxGeometry(0.3, 0.8, TANK_RADIUS * 2 + 0.2);
    const saddleLeft = new THREE.Mesh(saddleGeo, stainlessSteelMat);
    saddleLeft.position.set(-1.8, 0.4, 0);
    const saddleRight = new THREE.Mesh(saddleGeo, stainlessSteelMat);
    saddleRight.position.set(1.8, 0.4, 0);
    tankGroup.add(saddleLeft, saddleRight);

    const ringGeo = new THREE.TorusGeometry(TANK_RADIUS + 0.03, 0.04, 16, 48);
    const ringPositions = [-2.2, -1.1, 0, 1.1, 2.2];
    ringPositions.forEach((x) => {
        const ring = new THREE.Mesh(ringGeo, stainlessSteelMat);
        ring.rotation.y = Math.PI / 2;
        ring.position.set(x, Y_CENTER, 0);
        tankGroup.add(ring);
    });

    const manholeGeo = new THREE.CylinderGeometry(0.3, 0.3, 0.2, 24);
    const manhole1 = new THREE.Mesh(manholeGeo, stainlessSteelMat);
    manhole1.position.set(-1.2, TANK_RADIUS * 2 + 0.45, 0);
    const manhole2 = new THREE.Mesh(manholeGeo, stainlessSteelMat);
    manhole2.position.set(1.2, TANK_RADIUS * 2 + 0.45, 0);
    tankGroup.add(manhole1, manhole2);

    const glassGeo = new THREE.CylinderGeometry(TANK_RADIUS, TANK_RADIUS, TANK_LENGTH, 48);
    const glassMesh = new THREE.Mesh(glassGeo, glassShellMat);
    glassMesh.rotation.z = Math.PI / 2;
    glassMesh.position.set(0, Y_CENTER, 0);
    tankGroup.add(glassMesh);

    // --- 3. CLIPPED INNER FLUID BODY ---
    // Top-down GPU Clipping plane
    clipPlane = new THREE.Plane(new THREE.Vector3(0, -1, 0), Y_CENTER);

    const yellowFuelHex = props.fuelType === 'petrol' ? 0xfacc15 : 0xeab308;

    // Rendered in opaque queue so it is caught by glass refraction pass
    fluidBodyMaterial = new THREE.MeshStandardMaterial({
        color: yellowFuelHex,
        roughness: 0.2,
        metalness: 0.1,
        clippingPlanes: [clipPlane],
        clipShadows: true,
        side: THREE.DoubleSide
    });

    const fluidGeo = new THREE.CylinderGeometry(INNER_R, INNER_R, INNER_L, 48);
    const fluidMesh = new THREE.Mesh(fluidGeo, fluidBodyMaterial);
    fluidMesh.rotation.z = Math.PI / 2;
    fluidMesh.position.set(0, Y_CENTER, 0);
    tankGroup.add(fluidMesh);

    // --- 4. ANIMATED TOP WAVE SURFACE CAP ---
    const wavePlaneGeo = new THREE.PlaneGeometry(INNER_L, 1.0, 64, 32);

    waveMaterial = new THREE.ShaderMaterial({
        vertexShader: waveVertexShader,
        fragmentShader: waveFragmentShader,
        uniforms: {
            uTime: { value: 0.0 },
            uWaveFrequency: { value: 3.5 },
            uWaveAmplitude: { value: 0.03 },
            uColor: { value: new THREE.Color(yellowFuelHex) }
        },
        side: THREE.DoubleSide
    });

    waveSurfaceMesh = new THREE.Mesh(wavePlaneGeo, waveMaterial);
    waveSurfaceMesh.rotation.x = -Math.PI / 2; // Lie flat horizontally
    tankGroup.add(waveSurfaceMesh);

    scene.add(tankGroup);

    updateFluidLevel();
}

function updateFluidLevel() {
    if (!clipPlane || !waveSurfaceMesh) return;

    const pct = Math.max(0, Math.min(level.value, 1));

    // Compute exact liquid surface height along World Y
    const yMin = Y_CENTER - INNER_R;
    const yMax = Y_CENTER + INNER_R;
    const yLiquid = yMin + pct * (yMax - yMin);

    // 1. Set GPU Clipping Plane height
    clipPlane.constant = yLiquid;

    // 2. Compute cylinder chord width W = 2 * sqrt(R^2 - h^2) at height yLiquid
    const hRel = yLiquid - Y_CENTER;
    const chordWidth = 2 * Math.sqrt(Math.max(0.001, INNER_R * INNER_R - hRel * hRel));

    // 3. Position and scale wave surface cap
    waveSurfaceMesh.position.set(0, yLiquid, 0);
    waveSurfaceMesh.scale.y = chordWidth; // Scales across tank width (World Z)

    if (pct <= 0.005 || pct >= 0.995) {
        waveSurfaceMesh.visible = false;
    } else {
        waveSurfaceMesh.visible = true;
    }

    // Color updates
    const currentColor = isLow.value ? 0xf43f5e : (props.fuelType === 'petrol' ? 0xfacc15 : 0xeab308);
    if (fluidBodyMaterial) fluidBodyMaterial.color.setHex(currentColor);
    if (waveMaterial) waveMaterial.uniforms.uColor.value.setHex(currentColor);
}

function initThreeJS() {
    const container = mount.value;
    if (!container) return;

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0f172a);

    camera = new THREE.PerspectiveCamera(40, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 2.2, 8.5);

    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;

    // Enable GPU Local Clipping
    renderer.localClippingEnabled = true;

    container.appendChild(renderer.domElement);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 1.8, 0);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.maxPolarAngle = Math.PI / 2;

    const ambientLight = new THREE.AmbientLight(0xffffff, 1.2);
    scene.add(ambientLight);

    const mainLight = new THREE.DirectionalLight(0xffffff, 2.0);
    mainLight.position.set(5, 12, 8);
    scene.add(mainLight);

    buildScene();

    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        if (waveMaterial) {
            waveMaterial.uniforms.uTime.value = clock.getElapsedTime();
        }
        controls.update();
        renderer.render(scene, camera);
    };
    animate();
}

function handleResize() {
    if (!mount.value || !renderer) return;
    const w = mount.value.clientWidth;
    const h = mount.value.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
}

watch(level, updateFluidLevel);

onMounted(() => {
    try {
        initThreeJS();
        window.addEventListener('resize', handleResize);
    } catch (e) {
        console.error("Three.js initialization failed:", e);
        error.value = true;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
});
</script>
