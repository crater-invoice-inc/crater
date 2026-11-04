<template>
  <form
    enctype="multipart/form-data"
    class="
      relative
      flex
      items-center
      justify-center
      p-2
      border-2 border-dashed
      rounded-md
      cursor-pointer
      avatar-upload
      border-gray-200
      transition-all
      duration-300
      ease-in-out
      isolate
      w-full
      hover:border-gray-300
      group
      min-h-[100px]
      bg-gray-50
    "
    :class="avatar ? 'w-32 h-32' : 'w-full'"
  >
    <input
      id="file-upload"
      ref="inputRef"
      type="file"
      tabindex="-1"
      :multiple="multiple"
      :name="inputFieldName"
      :accept="accept"
      class="absolute z-10 w-full h-full opacity-0 cursor-pointer"
      @click="$event.target.value=null"
      @change="
        onChange(
          $event.target.name,
          $event.target.files,
          $event.target.files.length
        )
      "
    />

    <!-- Avatar Not Selected -->
    <div v-if="!localFiles.length && avatar" class="">
      <img :src="getDefaultAvatar()" class="rounded" alt="Default Avatar" />

      <a
        href="#"
        class="absolute z-30 bg-white rounded-full -bottom-3 -right-3 group"
        @click.prevent.stop="onBrowse"
      >
        <BaseIcon
          name="PlusCircleIcon"
          class="
            h-8
            text-xl
            leading-6
            text-primary-500
            group-hover:text-primary-600
          "
        />
      </a>
    </div>

    <!-- Not Selected -->
    <div v-else-if="!localFiles.length" class="flex flex-col items-center">
      <BaseIcon
        name="CloudUploadIcon"
        class="h-6 mb-2 text-xl leading-6 text-gray-400"
      />
      <p class="text-xs leading-4 text-center text-gray-400">
        Drag a file here or
        <a
          class="
            cursor-pointer
            text-primary-500
            hover:text-primary-600 hover:font-medium
            relative
            z-20
          "
          href="#"
          @click.prevent.stop="onBrowse"
        >
          browse
        </a>
        to choose a file
      </p>
      <p class="text-xs leading-4 text-center text-gray-400 mt-2">
        {{ recommendedText }}
      </p>
    </div>

    <div
      v-else-if="localFiles.length && avatar && !multiple"
      class="flex w-full h-full border border-gray-200 rounded"
    >
      <img
        v-if="localFiles[0].image"
        for="file-upload"
        :src="localFiles[0].image"
        class="block object-cover w-full h-full rounded opacity-100"
        style="animation: fadeIn 2s ease"
      />

      <div
        v-else
        class="
          flex
          justify-center
          items-center
          text-gray-400
          flex-col
          space-y-2
          px-2
          py-4
          w-full
        "
      >
        <!-- DocumentText Icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-8 w-8"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.25"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>

        <p
          v-if="localFiles[0].name"
          class="
            text-gray-600
            font-medium
            text-sm
            truncate
            overflow-hidden
            w-full
          "
        >
          {{ localFiles[0].name }}
        </p>
      </div>

      <a
        href="#"
        class="
          box-border
          absolute
          z-30
          flex
          items-center
          justify-center
          w-8
          h-8
          bg-white
          border border-gray-200
          rounded-full
          shadow-md
          -bottom-3
          -right-3
          group
          hover:border-gray-300
        "
        @click.prevent.stop="onAvatarRemove(localFiles[0])"
      >
        <BaseIcon name="XIcon" class="h-4 text-xl leading-6 text-black" />
      </a>
    </div>

    <!-- Preview Files Multiple -->
    <div
      v-else-if="localFiles.length && multiple"
      class="flex flex-wrap w-full"
    >
      <a
        v-for="(localFile, index) in localFiles"
        :key="localFile"
        href="#"
        class="
          block
          p-2
          m-2
          bg-white
          border border-gray-200
          rounded
          hover:border-gray-500
          relative
          max-w-md
        "
        @click.prevent
      >
        <img
          v-if="localFile.image"
          for="file-upload"
          :src="localFile.image"
          class="block object-cover w-20 h-20 opacity-100"
          style="animation: fadeIn 2s ease"
        />

        <div
          v-else
          class="
            flex
            justify-center
            items-center
            text-gray-400
            flex-col
            space-y-2
            px-2
            py-4
            w-full
          "
        >
          <!-- DocumentText Icon -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-8 w-8"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.25"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>

          <p
            v-if="localFile.name"
            class="
              text-gray-600
              font-medium
              text-sm
              truncate
              overflow-hidden
              w-full
            "
          >
            {{ localFile.name }}
          </p>
        </div>

        <a
          href="#"
          class="
            box-border
            absolute
            z-30
            flex
            items-center
            justify-center
            w-8
            h-8
            bg-white
            border border-gray-200
            rounded-full
            shadow-md
            -bottom-3
            -right-3
            group
            hover:border-gray-300
          "
          @click.prevent.stop="onFileRemove(index)"
        >
          <BaseIcon name="XIcon" class="h-4 text-xl leading-6 text-black" />
        </a>
      </a>
    </div>

    <div v-else class="flex w-full items-center justify-center">
      <a
        v-for="(localFile, index) in localFiles"
        :key="localFile"
        href="#"
        class="
          block
          p-2
          m-2
          bg-white
          border border-gray-200
          rounded
          hover:border-gray-500
          relative
          max-w-md
        "
        @click.prevent
      >
        <img
          v-if="localFile.image"
          for="file-upload"
          :src="localFile.image"
          class="block object-contain h-20 opacity-100 min-w-[5rem]"
          style="animation: fadeIn 2s ease"
        />

        <div
          v-else
          class="
            flex
            justify-center
            items-center
            text-gray-400
            flex-col
            space-y-2
            px-2
            py-4
            w-full
          "
        >
          <!-- DocumentText Icon -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-8 w-8"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.25"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>

          <p
            v-if="localFile.name"
            class="
              text-gray-600
              font-medium
              text-sm
              truncate
              overflow-hidden
              w-full
            "
          >
            {{ localFile.name }}
          </p>
        </div>

        <a
          href="#"
          class="
            box-border
            absolute
            z-30
            flex
            items-center
            justify-center
            w-8
            h-8
            bg-white
            border border-gray-200
            rounded-full
            shadow-md
            -bottom-3
            -right-3
            group
            hover:border-gray-300
          "
          @click.prevent.stop="onFileRemove(index)"
        >
          <BaseIcon name="XIcon" class="h-4 text-xl leading-6 text-black" />
        </a>
      </a>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import utils from '@/scripts/helpers/utilities'

const props = defineProps({
  multiple: {
    type: Boolean,
    default: false,
  },
  avatar: {
    type: Boolean,
    default: false,
  },
  autoProcess: {
    type: Boolean,
    default: false,
  },
  uploadUrl: {
    type: String,
    default: '',
  },
  preserveLocalFiles: {
    type: Boolean,
    default: false,
  },
  accept: {
    type: String,
    default: 'image/*',
  },
  inputFieldName: {
    type: String,
    default: 'photos',
  },
  base64: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
  recommendedText: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['change', 'remove', 'update:modelValue'])

// status
const STATUS_INITIAL = 0
const STATUS_SAVING = 1
const STATUS_SUCCESS = 2
const STATUS_FAILED = 3

let uploadedFiles = ref([])
const localFiles = ref([])
const inputRef = ref(null)
let uploadError = ref(null)
let currentStatus = ref(null)

function reset() {
  // reset form to initial state
  currentStatus = STATUS_INITIAL

  uploadedFiles.value = []

  if (props.modelValue && props.modelValue.length) {
    localFiles.value = [...props.modelValue]
  } else {
    localFiles.value = []
  }

  uploadError = null
}

function upload(formData) {
  return (
    axios
      .post(props.uploadUrl, formData)
      // get data
      .then((x) => x.data)
      // add url field
      .then((x) => x.map((img) => ({ ...img, url: `/images/${img.id}` })))
  )
}

// upload data to the server
function save(formData) {
  currentStatus = STATUS_SAVING

  upload(formData)
    .then((x) => {
      uploadedFiles = [].concat(x)
      currentStatus = STATUS_SUCCESS
    })
    .catch((err) => {
      uploadError = err.response
      currentStatus = STATUS_FAILED
    })
}

function getBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve(reader.result)
    reader.onerror = (error) => reject(error)
  })
}

function onChange(fieldName, fileList, fileCount) {
  if (!fileList.length) return

  if (props.multiple) {
    emit('change', fieldName, fileList, fileCount)
  } else {
    if (props.base64) {
      getBase64(fileList[0]).then((res) => {
        emit('change', fieldName, res, fileCount, fileList[0])
      })
    } else {
      emit('change', fieldName, fileList[0], fileCount)
    }
  }

  if (!props.preserveLocalFiles) {
    localFiles.value = []
  }

  Array.from(Array(fileList.length).keys()).forEach((x) => {
    const file = fileList[x]

    if (utils.isImageFile(file.type)) {
      getBase64(file).then((image) => {
        localFiles.value.push({
          fileObject: file,
          type: file.type,
          name: file.name,
          image,
        })
      })
    } else {
      localFiles.value.push({
        fileObject: file,
        type: file.type,
        name: file.name,
      })
    }
  })

  emit('update:modelValue', localFiles.value)

  if (!props.autoProcess) return

  // append the files to FormData
  const formData = new FormData()

  Array.from(Array(fileList.length).keys()).forEach((x) => {
    formData.append(fieldName, fileList[x], fileList[x].name)
  })

  // save it
  save(formData)
}

function onBrowse() {
  if (inputRef.value) {
    inputRef.value.click()
  }
}

function onAvatarRemove(image) {
  localFiles.value = []
  emit('remove', image)
}

function onFileRemove(index) {
  localFiles.value.splice(index, 1)
  emit('remove', index)
}

function getDefaultAvatar() {
  return import.meta.env.DEV
    ? '/img/default-avatar.jpg'
    : '/build/img/default-avatar.jpg'
}

onMounted(() => {
  reset()
})

watch(
  () => props.modelValue,
  (v) => {
    localFiles.value = [...v]
  }
)
</script>
