import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDownload, faRedo } from '@fortawesome/free-solid-svg-icons';
import switchVersion from '@/api/server/versions/switchVersion';
import { httpErrorToHuman } from '@/api/http';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import tw from 'twin.macro';

interface Props {
    group: number;
    method: string;
    versionKey: string;
    currentVersion: CurrentVersion;
    installed: () => void;
}

interface CurrentVersion {
    method: string;
    key: string;
}

export default ({ group, method, versionKey, currentVersion, installed }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const [ visible, setVisible ] = useState(false);
    const [ isLoading, setIsLoading ] = useState(false);
    const [ disabled, setDisabled ] = useState(false);
    const [ isDeleteFiles, setDeleteFiles ] = useState(0);

    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = () => {
        setDisabled(true);
        setIsLoading(true);
        clearFlashes('server:versions');

        switchVersion(uuid, group, method, versionKey, isDeleteFiles)
            .then(() => {
                setDisabled(false);
                setIsLoading(false);
                setVisible(false);
                installed();
            })
            .catch(error => {
                setDisabled(false);
                setIsLoading(false);
                setVisible(false);
                addError({ key: 'server:versions', message: httpErrorToHuman(error) });
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={visible}
                title={'Change version?'}
                buttonText={'Yes'}
                onConfirmed={submit}
                showSpinnerOverlay={isLoading}
                onModalDismissed={() => setVisible(false)}
            >
                Are you sure you want to delete this server?
            </ConfirmationModal>
            <Button css={tw`mr-2`} size={'xsmall'} color={isDeleteFiles === 1 ? 'red' : 'grey'} onClick={() => setDeleteFiles(isDeleteFiles === 1 ? 0 : 1)}>{isDeleteFiles === 1 ? 'Delete Files' : 'Not Delete Files'}</Button>
            <Button css={'float: right;'} isLoading={disabled} disabled={disabled} type={'button'} size={'xsmall'} color={(currentVersion.method === method && currentVersion.key === versionKey.toString() ? 'primary' : 'green')} onClick={() => setVisible(true)}>
                <FontAwesomeIcon icon={currentVersion.method === method && currentVersion.key === versionKey.toString() ? faRedo : faDownload} /> {currentVersion.method === method && currentVersion.key === versionKey.toString() ? 'Reinstall' : 'Install'}
            </Button>
        </>
    );
};
