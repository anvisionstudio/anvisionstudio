(function () {
	'use strict';

	var MAX_FILES = 20;
	var DEFAULT_QUALITY = 0.8;
	var MAX_BYTES = 1024 * 1024;
	var ACCEPT = ['image/jpeg', 'image/jpg', 'image/png'];

	var fileInput = document.getElementById('fileInput');
	var dropzone = document.getElementById('dropzone');
	var notice = document.getElementById('notice');
	var batchPanel = document.getElementById('batchPanel');
	var cropPanel = document.getElementById('cropPanel');
	var batchTableBody = document.querySelector('#batchTable tbody');
	var batchStatus = document.getElementById('batchStatus');
	var batchZip = document.getElementById('batchZip');
	var cropImage = document.getElementById('cropImage');
	var cropEstimate = document.getElementById('cropEstimate');
	var helpDialog = document.getElementById('helpDialog');

	var batchFiles = [];
	var batchResults = [];
	var cropper = null;
	var cropObjectUrl = '';
	var cropSourceName = 'image';
	var estimateTimer = 0;

	function showNotice(message) {
		notice.hidden = !message;
		notice.textContent = message || '';
	}

	function formatBytes(bytes) {
		if (bytes < 1024) {
			return bytes + ' B';
		}
		if (bytes < 1024 * 1024) {
			return (bytes / 1024).toFixed(1) + ' KB';
		}
		return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
	}

	function filterImages(fileList) {
		return Array.prototype.slice.call(fileList, 0).filter(function (file) {
			return ACCEPT.indexOf(file.type) !== -1;
		});
	}

	function resetUi() {
		if (cropper) {
			cropper.destroy();
			cropper = null;
		}
		if (cropObjectUrl) {
			URL.revokeObjectURL(cropObjectUrl);
			cropObjectUrl = '';
		}
		batchFiles = [];
		batchResults = [];
		batchTableBody.innerHTML = '';
		batchZip.disabled = true;
		batchPanel.hidden = true;
		cropPanel.hidden = true;
		dropzone.hidden = false;
		fileInput.value = '';
	}

	function handleFiles(fileList) {
		var files = filterImages(fileList);
		if (files.length === 0) {
			showNotice('請選擇 JPG 或 PNG 圖片。');
			return;
		}
		if (fileList.length > MAX_FILES || files.length > MAX_FILES) {
			showNotice('最多 20 張，已取前 20 張。');
		} else {
			showNotice('');
		}
		files = files.slice(0, MAX_FILES);
		if (files.length === 1) {
			enterCropMode(files[0]);
		} else {
			enterBatchMode(files);
		}
	}

	dropzone.addEventListener('click', function () {
		fileInput.click();
	});

	dropzone.addEventListener('keydown', function (event) {
		if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault();
			fileInput.click();
		}
	});

	fileInput.addEventListener('change', function (event) {
		handleFiles(event.target.files);
	});

	dropzone.addEventListener('dragover', function (event) {
		event.preventDefault();
		dropzone.classList.add('is-over');
	});

	dropzone.addEventListener('dragleave', function () {
		dropzone.classList.remove('is-over');
	});

	dropzone.addEventListener('drop', function (event) {
		event.preventDefault();
		dropzone.classList.remove('is-over');
		handleFiles(event.dataTransfer.files);
	});

	function loadImage(file) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onerror = function () {
				reject(new Error('無法讀取檔案'));
			};
			reader.onload = function () {
				var img = new Image();
				img.onload = function () {
					resolve(img);
				};
				img.onerror = function () {
					reject(new Error('無法載入圖片'));
				};
				img.src = reader.result;
			};
			reader.readAsDataURL(file);
		});
	}

	function canvasToBlob(canvas, quality) {
		return new Promise(function (resolve, reject) {
			canvas.toBlob(function (blob) {
				if (!blob) {
					reject(new Error('此瀏覽器無法輸出 WebP'));
					return;
				}
				resolve(blob);
			}, 'image/webp', quality);
		});
	}

	async function convertToWebp(file, targetWidth, quality, maxBytes) {
		quality = quality === undefined ? DEFAULT_QUALITY : quality;
		maxBytes = maxBytes === undefined ? MAX_BYTES : maxBytes;
		var img = await loadImage(file);
		var destWidth = Math.max(1, Math.min(targetWidth, img.width));
		var scale = destWidth / img.width;
		var canvas = document.createElement('canvas');
		canvas.width = destWidth;
		canvas.height = Math.max(1, Math.round(img.height * scale));
		canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);

		var blob = await canvasToBlob(canvas, quality);
		while (blob.size > maxBytes && canvas.width > 100) {
			canvas.width = Math.max(100, Math.round(canvas.width * 0.9));
			canvas.height = Math.max(1, Math.round(canvas.height * 0.9));
			canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
			blob = await canvasToBlob(canvas, quality);
		}
		return blob;
	}

	function triggerDownload(blob, filename) {
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = filename;
		a.click();
		URL.revokeObjectURL(url);
	}

	function webpName(name) {
		return name.replace(/\.\w+$/, '.webp');
	}

	function enterBatchMode(files) {
		resetUi();
		if (files.length > MAX_FILES) {
			showNotice('最多 20 張，已取前 20 張。');
			files = files.slice(0, MAX_FILES);
		}
		batchFiles = files;
		dropzone.hidden = true;
		batchPanel.hidden = false;
		batchStatus.textContent = '已選 ' + files.length + ' 張，按「開始轉換」。';
		files.forEach(function (file) {
			var tr = document.createElement('tr');
			tr.innerHTML =
				'<td></td><td>' +
				formatBytes(file.size) +
				'</td><td>—</td><td>—</td><td></td>';
			tr.cells[0].textContent = file.name;
			batchTableBody.appendChild(tr);
		});
	}

	document.getElementById('batchQuality').addEventListener('input', function (event) {
		document.getElementById('batchQualityVal').textContent = event.target.value + '%';
	});

	document.getElementById('batchConvert').addEventListener('click', async function () {
		var width = parseInt(document.getElementById('batchWidth').value, 10) || 1920;
		var quality = (parseInt(document.getElementById('batchQuality').value, 10) || 80) / 100;
		batchResults = [];
		batchZip.disabled = true;
		var rows = batchTableBody.rows;
		for (var i = 0; i < batchFiles.length; i++) {
			batchStatus.textContent = '轉換中 ' + (i + 1) + ' / ' + batchFiles.length;
			try {
				var blob = await convertToWebp(batchFiles[i], width, quality, MAX_BYTES);
				var name = webpName(batchFiles[i].name);
				batchResults.push({ name: name, blob: blob });
				var ratio = batchFiles[i].size
					? Math.round((1 - blob.size / batchFiles[i].size) * 100)
					: 0;
				rows[i].cells[2].textContent = formatBytes(blob.size);
				rows[i].cells[3].textContent = ratio + '%';
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'ic-btn ic-btn-secondary';
				btn.textContent = '下載';
				btn.addEventListener(
					'click',
					(function (fileName, fileBlob) {
						return function () {
							triggerDownload(fileBlob, fileName);
						};
					})(name, blob)
				);
				rows[i].cells[4].innerHTML = '';
				rows[i].cells[4].appendChild(btn);
			} catch (err) {
				rows[i].cells[2].textContent = '失敗';
				rows[i].cells[3].textContent = err.message || '錯誤';
			}
		}
		batchStatus.textContent = '完成 ' + batchResults.length + ' 張。';
		batchZip.disabled = batchResults.length === 0;
	});

	document.getElementById('batchZip').addEventListener('click', async function () {
		if (!batchResults.length || typeof JSZip === 'undefined') {
			return;
		}
		var zip = new JSZip();
		batchResults.forEach(function (item) {
			zip.file(item.name.replace(/\.\w+$/, '.webp'), item.blob);
		});
		var zipBlob = await zip.generateAsync({ type: 'blob' });
		triggerDownload(zipBlob, 'compressed.zip');
	});

	document.getElementById('resetBatch').addEventListener('click', resetUi);

	function getCropWidth() {
		var preset = document.getElementById('cropWidthPreset').value;
		if (preset === 'custom') {
			return parseInt(document.getElementById('cropWidthCustom').value, 10) || 1600;
		}
		return parseInt(preset, 10) || 1920;
	}

	function getCropQuality() {
		return (parseInt(document.getElementById('cropQuality').value, 10) || 80) / 100;
	}

	async function exportCropped(outputWidth, quality) {
		quality = quality === undefined ? DEFAULT_QUALITY : quality;
		if (!cropper) {
			throw new Error('尚未載入裁切器');
		}
		var croppedCanvas = cropper.getCroppedCanvas({ width: outputWidth });
		return canvasToBlob(croppedCanvas, quality);
	}

	function scheduleEstimate() {
		window.clearTimeout(estimateTimer);
		estimateTimer = window.setTimeout(updatePreviewSize, 200);
	}

	async function updatePreviewSize() {
		if (!cropper) {
			return;
		}
		try {
			var blob = await exportCropped(getCropWidth(), getCropQuality());
			cropEstimate.textContent = formatBytes(blob.size);
		} catch (err) {
			cropEstimate.textContent = '無法預估';
		}
	}

	function enterCropMode(file) {
		resetUi();
		cropSourceName = file.name;
		dropzone.hidden = true;
		cropPanel.hidden = false;
		cropObjectUrl = URL.createObjectURL(file);
		cropImage.src = cropObjectUrl;
		cropImage.onload = function () {
			cropper = new Cropper(cropImage, {
				viewMode: 1,
				aspectRatio: NaN,
				autoCropArea: 1,
				responsive: true,
				background: false,
				crop: scheduleEstimate,
			});
			scheduleEstimate();
		};
	}

	document.getElementById('ratioButtons').addEventListener('click', function (event) {
		var button = event.target.closest('button[data-ratio]');
		if (!button || !cropper) {
			return;
		}
		document.querySelectorAll('#ratioButtons button').forEach(function (el) {
			el.classList.toggle('is-active', el === button);
		});
		var ratio = button.getAttribute('data-ratio');
		cropper.setAspectRatio(ratio === 'free' ? NaN : parseFloat(ratio));
		scheduleEstimate();
	});

	document.getElementById('cropWidthPreset').addEventListener('change', function (event) {
		document.getElementById('cropWidthCustomWrap').hidden = event.target.value !== 'custom';
		scheduleEstimate();
	});

	document.getElementById('cropWidthCustom').addEventListener('input', scheduleEstimate);
	document.getElementById('cropQuality').addEventListener('input', function (event) {
		document.getElementById('cropQualityVal').textContent = event.target.value + '%';
		scheduleEstimate();
	});

	document.getElementById('cropDownload').addEventListener('click', async function () {
		try {
			var blob = await exportCropped(getCropWidth(), getCropQuality());
			triggerDownload(blob, webpName(cropSourceName));
		} catch (err) {
			showNotice(err.message || '下載失敗');
		}
	});

	document.getElementById('resetCrop').addEventListener('click', resetUi);

	document.getElementById('helpBtn').addEventListener('click', function () {
		helpDialog.showModal();
	});
	document.getElementById('helpClose').addEventListener('click', function () {
		helpDialog.close();
	});
})();
