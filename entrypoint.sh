#!/bin/bash
# Entrypoint file. Allows running a set of commands in the host machine in order to configure or finalize build processes.

# If we can run avahi-publish in the host machine then we can execute the publishing of commands 
if command -v avahi-publish -h &> /dev/null
then
    echo "Publishing mDNS for api.edgebox.local"
    avahi-publish -a -R api.edgebox.local $(hostname -I | awk '{print $1}') &
fi