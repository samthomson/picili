import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { HttpService } from '../../../services';

import { GlobalVars } from './../../../../env';

@Component({
  selector: 'app-settings',
  templateUrl: './settings.component.html'
})
export class SettingsComponent implements OnInit {

    private oSettings: any;

    private sTab: string = 'file-sources';

    private sDropboxFolderPathInput: string = '';
    private bSavingDropboxFolderPath: boolean = false;

    private sStartedSaving: string = '';
    private sFinishedSaving: string = '';


    private bDisconnectingDropbox: boolean = false;

    constructor(
        private route: ActivatedRoute,
        private gbl: GlobalVars,
        private httpService: HttpService
    ) {
        this.oSettings = this.route.snapshot.data['userSettings'];

        if(this.oSettings.dropbox !== null)
        {
            this.sDropboxFolderPathInput = this.oSettings.dropbox.folder;
        }
    }

    ngOnInit() {
    }

    goDropbox()
    {
        return this.httpService.dropboxOAuth();
    }

    onSaveDropboxFolderPath()
    {
        this.bSavingDropboxFolderPath = true;

        this.httpService.updateDropboxFolder(this.sDropboxFolderPathInput)
            .subscribe(
                (data) => {
                    this.oSettings.dropbox.sFolderPath = this.sDropboxFolderPathInput;

                    this.bSavingDropboxFolderPath = false;
                },
                (err) => {
                    this.bSavingDropboxFolderPath = false;
                }
            );

    }

    onDisconnectDropbox()
    {
        // confirm
        if(confirm('Disconnect Dropbox? Picili will loose all your dropbox files.'))
        {
            // start loading
            this.bDisconnectingDropbox = true;

            this.httpService.disconnectDropbox()
                .subscribe(
                (data) => {
                    // send delete to server
                    this.oSettings.dropbox = null;
                    this.bDisconnectingDropbox = false;
                }
            );
        }
    }

    onPrivacyCheckChange(bNewValue)
    {
        this.sFinishedSaving = "";
        this.oSettings.public = bNewValue;

        this.sStartedSaving = "Saving..";
        this.httpService.updatePrivacy(bNewValue)
            .subscribe(
                (data) => {
                    this.sStartedSaving = "";
                    this.sFinishedSaving = "Saved";
                    setTimeout(
                        function(){
                            this.sFinishedSaving = "";
                        },
                        2000
                    );
                }
            );
    }
}
