<!-- CREATE REQUEST MODAL -->
<div class="modal fade" id="fileRequestCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Create new request') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="fileRequestCreateForm">
          @csrf
          {{-- NEW: tell backend this is from Doc requests page --}}
          <input type="hidden" name="is_doc_request" value="1">

          <div class="mb-3">
            <label class="form-label">{{ __('Title') }}</label>
            <input type="text" name="title" class="form-control"
                   placeholder="{{ __('Explain what the request is for') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Description (optional)') }}</label>
            <textarea name="description" rows="3" class="form-control"
                      placeholder="{{ __('Add any extra details about the request') }}"></textarea>
          </div>

          {{-- Folder name --}}
          <div class="mb-3">
              <label class="form-label">{{ __('Folder name') }}</label>
              <input type="text"
                    name="folder_name"
                    id="frFolderNameInput"
                    class="form-control"
                    placeholder="{{ __('Name of the folder to create for uploads') }}">
              <small class="text-muted">
                  {{ __('If left empty, a default name like "Request 2025-12-04 12:34:56.123456" will be used.') }}
              </small>
          </div>

          {{-- Folder --}}
          <div class="mb-3">
              <label class="form-label">{{ __('Folder for uploaded files') }}</label>
              <div class="d-flex align-items-center">
                  <div class="flex-grow-1">
                      <input type="text"
                        class="form-control bg-light"
                        id="frSelectedFolderText"
                        value="{{ __('Root') }}"
                        data-default-label="{{ __('Root') }}"
                        readonly>
                  </div>
                  <button type="button" class="btn btn-outline-secondary ms-2" id="btnChangeFolder">
                      {{ __('Change folder') }}
                  </button>
              </div>
              <input type="hidden" name="folder_shared_id" id="frSelectedFolderId" value="">
              <small class="text-muted d-block mt-1">
                  {{ __('Only you will have access to this folder. The request folder will be created inside the selected location.') }}
              </small>
          </div>


          {{-- Expiration & password --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Deadline date') }}</label>
              <input type="date" name="expiration_date" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Deadline time') }}</label>
              <input type="time" name="expiration_time" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Total storage limit (optional)') }}</label>
            <div class="input-group">
                <input type="number" name="storage_limit_value" class="form-control" placeholder="e.g. 100" min="1">
                <select name="storage_limit_unit" class="form-select" style="max-width: 100px;">
                    <option value="MB">MB</option>
                    <option value="GB">GB</option>
                </select>
            </div>
            <div class="form-text text-muted">
                {{ __('Limit the total size of uploads. Leave empty for unlimited (up to your account storage).') }}
            </div>
          </div>

          <div class="mb-3">
              <label class="form-label">{{ __('Password (optional)') }}</label>
              <div class="input-group">
                  <input type="password" name="password" class="form-control" id="createPassword"
                        placeholder="{{ __('Add a password to protect this link') }}" autocomplete="new-password">
                  <button class="btn btn-outline-secondary d-none" type="button"
                          id="btnToggleCreatePassword" title="{{ __('Show/Hide Password') }}">
                      <i class="fas fa-eye" id="toggleCreatePasswordIcon"></i>
                  </button>
              </div>
              <small class="form-text text-muted">
                  {{ __('Leave empty if you don’t want to protect this link with a password.') }}
              </small>
          </div>


          <div class="alert alert-info small">
            <i class="fas fa-info-circle me-1"></i>
            {{ __('If no deadline is set, the link will expire in 24 hours.') }}
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          {{ __('Cancel') }}
        </button>
        <button type="button" class="btn btn-primary" id="btnCreateRequestSubmit">
          <i class="fas fa-check me-2"></i>{{ __('Create') }}
        </button>
      </div>
    </div>
  </div>
</div>


{{-- SHARE REQUEST MODAL --}}
<div class="modal fade" id="fileRequestShareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Share file request') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="fileRequestShareForm">
          @csrf
          <input type="hidden" id="shareFileRequestId">
          <div class="mb-3">
            <label class="form-label">{{ __('To') }}</label>
            <input type="text" class="form-control" id="shareEmails"
                   placeholder="{{ __('Email addresses, separated by commas') }}">
          </div>
          <div class="mb-3">
            <label class="form-label">{{ __('Message (optional)') }}</label>
            <textarea class="form-control" id="shareMessage" rows="3"></textarea>
          </div>

          <div class="border-top pt-3 mt-2">
            <label class="form-label">{{ __('Share a link instead') }}</label>
            <div class="input-group">
              <input type="text" class="form-control" id="shareLinkInput" readonly>
              <button class="btn btn-outline-secondary" type="button" id="btnCopyShareLink">
                {{ __('Copy') }}
              </button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          {{ __('Close') }}
        </button>
        <button type="button" class="btn btn-primary" id="btnSendShareRequest">
          {{ __('Send request') }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- MANAGE REQUEST MODAL --}}
<div class="modal fade" id="fileRequestManageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manageModalTitle">{{ __('File request') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="fileRequestManageForm">
          @csrf
          @method('PUT')
          <input type="hidden" id="manageFileRequestId">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="small text-muted">
              <span id="manageViewsCount">0</span> {{ __('views') }} •
              <span id="manageUploadsCount">0</span> {{ __('uploads') }}
            </div>
            <div>
              <span class="badge bg-success d-none" id="manageStatusBadgeOpen">{{ __('Open') }}</span>
              <span class="badge bg-secondary d-none" id="manageStatusBadgeClosed">{{ __('Closed') }}</span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Title') }}</label>
            <input type="text" class="form-control" name="title" id="manageTitle">
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Description (optional)') }}</label>
            <textarea class="form-control" name="description" id="manageDescription" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Folder for uploaded files') }}</label>
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <input type="text" class="form-control bg-light" id="manageFolderText" readonly>
              </div>
              <button type="button" class="btn btn-outline-secondary ms-2" id="manageChangeFolder">
                {{ __('Change folder') }}
              </button>
            </div>
            <input type="hidden" name="folder_shared_id" id="manageFolderId">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Deadline date') }}</label>
              <input type="date" class="form-control" name="expiration_date" id="manageExpirationDate">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Deadline time') }}</label>
              <input type="time" class="form-control" name="expiration_time" id="manageExpirationTime">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Total storage limit (optional)') }}</label>
            <div class="input-group">
                <input type="number" name="storage_limit_value" class="form-control" id="manageStorageLimitValue" placeholder="e.g. 100" min="1">
                <select name="storage_limit_unit" class="form-select" id="manageStorageLimitUnit" style="max-width: 100px;">
                    <option value="MB">MB</option>
                    <option value="GB">GB</option>
                </select>
            </div>
            <div class="form-text text-muted">
                {{ __('Limit the total size of uploads. Leave empty for unlimited (up to your account storage).') }}
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Password') }}</label>
            
            {{-- Password Status Indicator --}}
            <div class="mb-2">
              <span class="badge bg-success d-none" id="passwordStatusBadge">
                <i class="fas fa-lock me-1"></i> {{ __('Password Protected') }}
              </span>
              <span class="badge bg-warning text-dark d-none" id="passwordRemovalBadge">
                <i class="fas fa-exclamation-triangle me-1"></i> {{ __('Password will be removed on save') }}
              </span>
            </div>
            
            <div class="input-group">
              <input type="password" class="form-control" name="password" id="managePassword"
                     placeholder="{{ __('Enter new password') }}" autocomplete="new-password">
              <button class="btn btn-outline-secondary d-none" type="button" id="btnTogglePassword" title="{{ __('Show/Hide Password') }}">
                <i class="fas fa-eye" id="togglePasswordIcon"></i>
              </button>
              <button class="btn btn-outline-secondary" type="button" id="btnRemovePassword" title="{{ __('Remove Password') }}">
                <i class="fas fa-times"></i> {{ __('Remove') }}
              </button>
            </div>
            <input type="hidden" name="remove_password" id="manageRemovePassword" value="0">
            <small class="form-text text-muted" id="managePasswordHelp">
              {{ __('Leave blank to keep current password') }}
            </small>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger me-auto" id="btnCloseFileRequest">
          {{ __('Close request') }}
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          {{ __('Cancel') }}
        </button>
        <button type="button" class="btn btn-primary" id="btnSaveFileRequest">
          {{ __('Save') }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- FOLDER PICKER MODAL --}}
<div class="modal fade" id="folderPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Select Folder') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="list-group" id="folderPickerList">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">{{ __('Loading...') }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          {{ __('Cancel') }}
        </button>
      </div>
    </div>
  </div>
</div>
