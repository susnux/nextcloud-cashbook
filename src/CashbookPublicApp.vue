<script setup lang="ts">
/*!
 * SPDX-FileCopyrightText: 2025 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
</script>

<template>
	<RouterView />
</template>

<style>
#content.app-public {
	display: block;
	height: fit-content;
	margin: 0;
	padding: 0;
	position: unset;
}
#body-public.layout-base {
	position: unset;
	overflow: scroll;
	background: var(--color-main-background);
}
html:has(#body-public.layout-base) {
	background: var(--color-main-background);
}
</style>