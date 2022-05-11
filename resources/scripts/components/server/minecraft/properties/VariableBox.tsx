//! React
import React, { memo, useState } from 'react';

//! Components
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import InputSpinner from '@/components/elements/InputSpinner';
import Input from '@/components/elements/Input';
import FlashMessageRender from '@/components/FlashMessageRender';

//! States & Plugins
import { ServerContext } from '@/state/server';
import { usePermissions } from '@/plugins/usePermissions';
import useFlash from '@/plugins/useFlash';

//! API
import getServerProperties, { ServerVariable } from '@/api/swr/minecraft/getServerProperties';
import updateServerPropertiesVariable from '@/api/swr/minecraft/updateServerPropertiesVariable';

//! Vendors
import isEqual from 'react-fast-compare';
import tw from 'twin.macro';
import { debounce } from 'debounce';

interface Props {
    variable: ServerVariable;
}

const VariableBox = ({ variable }: Props) => {
    const FLASH_KEY = `server:properties:${variable.envVariable}`;

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const [ loading, setLoading ] = useState(false);
    const [ canEdit ] = usePermissions([ 'file.update' ]);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerProperties(uuid);

    const setVariableValue = debounce((value: string) => {
        setLoading(true);
        clearFlashes(FLASH_KEY);

        updateServerPropertiesVariable(uuid, variable.envVariable, value)
            .then(() => mutate())
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: FLASH_KEY });
            })
            .then(() => setLoading(false));
    }, 500);

    return (
        <TitledGreyBox
            title={
                <p css={tw`text-sm uppercase`}>
                    {variable.envVariable}
                </p>
            }
        >
            <FlashMessageRender byKey={FLASH_KEY} css={tw`mb-2 md:mb-4`}/>
            <InputSpinner visible={loading}>
                <Input
                    onKeyUp={e => canEdit && setVariableValue(e.currentTarget.value)}
                    readOnly={!canEdit}
                    name={variable.envVariable}
                    defaultValue={variable.serverValue}
                    placeholder={variable.serverValue}
                />
            </InputSpinner>
        </TitledGreyBox>
    );
};

export default memo(VariableBox, isEqual);
